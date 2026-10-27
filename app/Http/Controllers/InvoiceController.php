<?php

namespace App\Http\Controllers;

use App\Helpers\IndianFormat;
use App\Http\Controllers\Concerns\PreparesDocumentLines;
use App\Models\BankAccount;
use App\Models\ChargeType;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\InvoiceItemSerial;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\State;
use App\Models\Uom;
use App\Services\AmountInWords;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use App\Services\TaxCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class InvoiceController extends Controller
{
    use PreparesDocumentLines;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $invoices = Invoice::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('buyer_gstin', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('invoice_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', compact('invoices', 'search', 'status'));
    }

    public function create(Request $request): View
    {
        return view('invoices.create', array_merge($this->lookups(), [
            'invoice' => null,
            'itemRows' => [[
                'product_id' => '',
                'description' => '',
                'quantity' => 1,
                'uom' => '',
                'rate' => '',
                'discount_percent' => 0,
                'tax_rate' => '',
            ]],
            'chargeRows' => [$this->defaultChargeRow()],
            'prefillCustomer' => $request->query('customer_id'),
        ]));
    }

    public function store(Request $request, TaxCalculationService $tax): RedirectResponse
    {
        $invoice = $this->persist($request, $tax, null);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Draft invoice saved. Select serials and issue it to lock the number and stock.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'items.product.serials' => fn ($query) => $query->where('status', 'in_stock'),
            'items.serials',
            'charges',
            'taxSummary',
            'customer',
            'quotation',
            'proforma',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice): View|RedirectResponse
    {
        if (! $invoice->isEditable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $invoice->load(['items', 'charges']);

        return view('invoices.create', array_merge($this->lookups(), [
            'invoice' => $invoice,
            'itemRows' => $invoice->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all(),
            'chargeRows' => $invoice->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'amount' => $charge->amount,
            ])->all() ?: [$this->defaultChargeRow()],
            'prefillCustomer' => null,
        ]));
    }

    public function update(Request $request, Invoice $invoice, TaxCalculationService $tax): RedirectResponse
    {
        if (! $invoice->isEditable()) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('error', 'Only draft invoices can be edited.');
        }

        $this->persist($request, $tax, $invoice);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Draft invoice updated.');
    }

    public function issue(Request $request, Invoice $invoice, DocumentNumberService $numbers, StockService $stock): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $invoice, $numbers, $stock) {
                $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
                if ($locked->status !== 'draft') {
                    throw ValidationException::withMessages([
                        'status' => 'Only a draft invoice can be issued.',
                    ]);
                }

                $locked->load(['items.product']);
                $selected = $request->input('serials', []);
                $usedSerials = [];

                foreach ($locked->items as $item) {
                    $product = $item->product;
                    if (! $product || $product->product_type === 'service' || ! $product->track_serial) {
                        continue;
                    }

                    $needed = (int) round((float) $item->quantity);
                    if (abs((float) $item->quantity - $needed) > 0.0001) {
                        throw ValidationException::withMessages([
                            'serials' => $item->label().' must have a whole-number quantity when serials are tracked.',
                        ]);
                    }

                    $ids = array_values(array_filter((array) ($selected[$item->id] ?? [])));
                    if (count($ids) !== $needed) {
                        throw ValidationException::withMessages([
                            'serials' => 'Select '.$needed.' in-stock serial(s) for '.$item->label().'.',
                        ]);
                    }

                    foreach ($ids as $id) {
                        if (isset($usedSerials[$id])) {
                            throw ValidationException::withMessages([
                                'serials' => 'The same serial cannot be used on more than one line.',
                            ]);
                        }
                        $usedSerials[$id] = true;
                    }
                }

                if (str_starts_with($locked->invoice_number, 'DRAFT')) {
                    $locked->invoice_number = $numbers->next('invoice');
                }

                foreach ($locked->items as $item) {
                    $product = $item->product;
                    if (! $product || $product->product_type === 'service') {
                        continue;
                    }

                    if ($product->track_serial) {
                        $ids = array_values(array_filter((array) ($selected[$item->id] ?? [])));
                        $serials = ProductSerial::query()
                            ->whereIn('id', $ids)
                            ->where('product_id', $product->id)
                            ->where('status', 'in_stock')
                            ->lockForUpdate()
                            ->get();

                        if ($serials->count() !== count($ids)) {
                            throw ValidationException::withMessages([
                                'serials' => 'One or more serials for '.$item->label().' are no longer in stock.',
                            ]);
                        }

                        foreach ($serials as $serial) {
                            $stock->stockOut(
                                $product->id,
                                1,
                                'invoice',
                                $locked->id,
                                $locked->invoice_number,
                                $serial->serial_number,
                                $serial->id,
                                $serial->warehouse_id,
                                'Issued on '.$locked->invoice_number,
                                auth()->id(),
                            );
                            $serial->update([
                                'status' => 'sold',
                                'invoice_id' => $locked->id,
                            ]);
                            InvoiceItemSerial::create([
                                'invoice_item_id' => $item->id,
                                'product_serial_id' => $serial->id,
                                'serial_number' => $serial->serial_number,
                            ]);
                        }
                    } else {
                        $stock->stockOut(
                            $product->id,
                            (float) $item->quantity,
                            'invoice',
                            $locked->id,
                            $locked->invoice_number,
                            null,
                            null,
                            null,
                            'Issued on '.$locked->invoice_number,
                            auth()->id(),
                        );
                    }
                }

                $locked->status = 'issued';
                $locked->issued_at = now();
                $locked->balance_due = $locked->grand_total;
                $locked->amount_paid = 0;
                $locked->updated_by = auth()->id();
                $locked->save();
            });
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice issued. Number and stock are now locked.');
    }

    public function cancel(Invoice $invoice, StockService $stock): RedirectResponse
    {
        if ($invoice->status === 'cancelled') {
            return back()->with('error', 'This invoice is already cancelled.');
        }

        if ((float) $invoice->amount_paid > 0) {
            return back()->with('error', 'Remove receipt allocations before cancelling this invoice.');
        }

        try {
            DB::transaction(function () use ($invoice, $stock) {
                $locked = Invoice::lockForUpdate()->findOrFail($invoice->id);
                $locked->load(['items.product', 'items.serials']);

                if (in_array($locked->status, ['issued', 'partially_paid', 'paid'], true)) {
                    foreach ($locked->items as $item) {
                        $product = $item->product;
                        if (! $product || $product->product_type === 'service') {
                            continue;
                        }

                        if ($product->track_serial && $item->serials->isNotEmpty()) {
                            foreach ($item->serials as $link) {
                                $serialRow = $link->product_serial_id
                                    ? ProductSerial::find($link->product_serial_id)
                                    : null;
                                $stock->stockIn(
                                    $product->id,
                                    1,
                                    'invoice_reversal',
                                    $locked->id,
                                    $locked->invoice_number,
                                    null,
                                    null,
                                    $serialRow?->warehouse_id,
                                    'Cancellation of '.$locked->invoice_number,
                                    auth()->id(),
                                );
                                if ($link->product_serial_id) {
                                    ProductSerial::where('id', $link->product_serial_id)->update([
                                        'status' => 'in_stock',
                                        'invoice_id' => null,
                                    ]);
                                }
                            }
                        } else {
                            $stock->stockIn(
                                $product->id,
                                (float) $item->quantity,
                                'invoice_reversal',
                                $locked->id,
                                $locked->invoice_number,
                                null,
                                null,
                                null,
                                'Cancellation of '.$locked->invoice_number,
                                auth()->id(),
                            );
                        }
                    }
                }

                $locked->status = 'cancelled';
                $locked->balance_due = 0;
                $locked->updated_by = auth()->id();
                $locked->save();
            });
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice cancelled and stock reversed where it had been issued.');
    }

    public function pdf(Invoice $invoice): Response
    {
        $invoice->load(['items.serials', 'items.product', 'charges', 'taxSummary', 'customer']);
        $company = $this->companyProfile()?->loadMissing('state');
        $bank = BankAccount::where('is_default', true)->first()
            ?? BankAccount::where('is_active', true)->first();

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => $company,
            'bank' => $bank,
        ])->setPaper('a4')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 96,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        $filename = preg_replace('/[\/\\\\]+/', '-', $invoice->invoice_number).'.pdf';

        return $pdf->stream($filename);
    }

    public function persist(
        Request $request,
        TaxCalculationService $tax,
        ?Invoice $invoice,
        bool $validated = false,
    ): Invoice {
        $data = $validated ? $request->all() : $request->validate($this->rules());

        $customer = Customer::findOrFail($data['customer_id']);
        $context = $this->customerContext($customer, $data['place_of_supply_code'] ?? null);
        $seller = $this->sellerStateCode();
        $pos = (string) ($context['pos_code'] ?: ($data['place_of_supply_code'] ?? ''));

        if ($pos === '') {
            throw ValidationException::withMessages([
                'place_of_supply_code' => 'Place of supply is required.',
            ]);
        }

        $company = $this->companyProfile();
        if ($company?->books_locked_upto && Carbon::parse($data['invoice_date'])->lte($company->books_locked_upto)) {
            throw ValidationException::withMessages([
                'invoice_date' => 'Books are locked up to '.IndianFormat::date($company->books_locked_upto).'.',
            ]);
        }

        $interState = strtoupper(trim($seller)) !== strtoupper(trim($pos));
        $prepared = $this->prepareLines($data['items'] ?? [], $data['charges'] ?? [], $tax, $interState);
        $totals = $tax->calculate($prepared['tax_lines'], $seller, $pos, $this->roundOffRule());

        if ((float) $customer->credit_limit > 0) {
            $open = (float) Invoice::query()
                ->where('customer_id', $customer->id)
                ->whereIn('status', ['issued', 'partially_paid'])
                ->when($invoice, fn ($query) => $query->where('id', '!=', $invoice->id))
                ->sum('balance_due');
            $projected = round($open + (float) $totals['grand_total'], 2);
            if ($projected > (float) $customer->credit_limit + 0.009) {
                $message = 'Credit limit exceeded. Open balance plus this invoice is '
                    .IndianFormat::money($projected)
                    .' against a limit of '
                    .IndianFormat::money($customer->credit_limit).'.';
                if ($company?->credit_limit_hard_block) {
                    throw ValidationException::withMessages(['customer_id' => $message]);
                }
                session()->flash('warning', $message);
            }
        }

        $term = ! empty($data['payment_term_id'])
            ? PaymentTerm::find($data['payment_term_id'])
            : ($customer->payment_term_id ? PaymentTerm::find($customer->payment_term_id) : null);
        $dueDate = $data['due_date'] ?? null;
        if (! $dueDate && $term) {
            $dueDate = Carbon::parse($data['invoice_date'])->addDays((int) $term->due_days)->toDateString();
        }

        return DB::transaction(function () use ($invoice, $data, $customer, $context, $prepared, $totals, $term, $dueDate, $pos) {
            $shipSame = filter_var($data['ship_same'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $attributes = [
                'invoice_date' => $data['invoice_date'],
                'financial_year_id' => FinancialYear::where('is_current', true)->value('id'),
                'quotation_id' => $data['quotation_id'] ?? $invoice?->quotation_id,
                'proforma_id' => $data['proforma_id'] ?? $invoice?->proforma_id,
                'customer_id' => $customer->id,
                'buyer_name' => $customer->trade_name ?: $customer->legal_name,
                'buyer_address' => $context['billing_address'],
                'buyer_gstin' => $customer->gstin,
                'buyer_state' => $context['billing_state'],
                'buyer_state_code' => $context['billing_state_code'],
                'buyer_contact' => $customer->mobile,
                'consignee_name' => $shipSame ? ($customer->trade_name ?: $customer->legal_name) : ($data['consignee_name'] ?? $customer->legal_name),
                'consignee_address' => $shipSame ? $context['billing_address'] : ($data['consignee_address'] ?? $context['shipping_address']),
                'consignee_gstin' => $shipSame ? $customer->gstin : ($data['consignee_gstin'] ?? $customer->gstin),
                'consignee_state' => $shipSame ? $context['billing_state'] : $context['shipping_state'],
                'consignee_state_code' => $shipSame ? $context['billing_state_code'] : $context['shipping_state_code'],
                'consignee_contact' => $customer->mobile,
                'place_of_supply' => $context['pos_name'],
                'place_of_supply_code' => $pos,
                'tax_type' => $totals['tax_type'],
                'reference_no' => $data['reference_no'] ?? null,
                'reference_date' => $data['reference_date'] ?? null,
                'buyer_order_no' => $data['buyer_order_no'] ?? null,
                'buyer_order_date' => $data['buyer_order_date'] ?? null,
                'dispatched_through' => $data['dispatched_through'] ?? null,
                'destination' => $data['destination'] ?? null,
                'lr_rr_no' => $data['lr_rr_no'] ?? null,
                'vehicle_no' => $data['vehicle_no'] ?? null,
                'terms_of_delivery' => $data['terms_of_delivery'] ?? null,
                'mode_terms_of_payment' => $term?->name,
                'payment_term_id' => $term?->id,
                'due_date' => $dueDate,
                'subtotal' => $prepared['gross_total'],
                'discount_amount' => $prepared['discount_total'],
                'taxable_amount' => $totals['taxable_amount'],
                'cgst_amount' => $totals['cgst_amount'],
                'sgst_amount' => $totals['sgst_amount'],
                'igst_amount' => $totals['igst_amount'],
                'round_off' => $totals['round_off'],
                'grand_total' => $totals['grand_total'],
                'amount_paid' => 0,
                'balance_due' => $totals['grand_total'],
                'amount_in_words' => AmountInWords::convert($totals['grand_total']),
                'tax_amount_in_words' => AmountInWords::taxAmount($totals['tax_amount']),
                'supply_type' => $customer->gstin ? 'B2B' : 'B2C',
                'notes' => $data['notes'] ?? null,
                'updated_by' => auth()->id(),
            ];

            if ($invoice) {
                $invoice->update($attributes);
            } else {
                $invoice = Invoice::create([
                    ...$attributes,
                    'invoice_number' => $this->draftNumber(),
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }

            $invoice->items()->delete();
            $invoice->charges()->delete();
            $invoice->taxSummary()->delete();

            foreach ($prepared['items'] as $index => $item) {
                $invoice->items()->create([
                    'sr_no' => $index + 1,
                    'product_id' => $item['product_id'],
                    'item_code' => $item['item_code'] ?? null,
                    'product_name' => $item['product_name'] ?? null,
                    'description' => $item['description'],
                    'secondary_description' => $item['secondary_description'],
                    'hsn_sac' => $item['hsn_sac'],
                    'quantity' => $item['quantity'],
                    'uom' => $item['uom'],
                    'rate' => $item['rate'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                    'amount' => $item['amount'],
                    'tax_rate' => $item['tax_rate'],
                    'cgst_rate' => $item['cgst_rate'],
                    'sgst_rate' => $item['sgst_rate'],
                    'igst_rate' => $item['igst_rate'],
                    'cgst_amount' => $item['cgst_amount'],
                    'sgst_amount' => $item['sgst_amount'],
                    'igst_amount' => $item['igst_amount'],
                    'tax_amount' => $item['tax_amount'],
                    'is_service' => $item['is_service'],
                ]);
            }

            foreach ($prepared['charges'] as $charge) {
                $invoice->charges()->create([
                    'charge_type_id' => $charge['charge_type_id'],
                    'name' => $charge['name'],
                    'hsn_sac' => $charge['hsn_sac'],
                    'amount' => $charge['amount'],
                    'tax_rate' => $charge['tax_rate'],
                    'tax_amount' => $charge['tax_amount'],
                ]);
            }

            foreach ($totals['hsn_summary'] as $row) {
                $invoice->taxSummary()->create($row);
            }

            return $invoice->fresh(['items', 'charges', 'taxSummary']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'invoice_date' => ['required', 'date'],
            'place_of_supply_code' => ['required', 'exists:states,code'],
            'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
            'due_date' => ['nullable', 'date'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'reference_date' => ['nullable', 'date'],
            'buyer_order_no' => ['nullable', 'string', 'max:255'],
            'buyer_order_date' => ['nullable', 'date'],
            'dispatched_through' => ['nullable', 'string', 'max:255'],
            'destination' => ['nullable', 'string', 'max:255'],
            'lr_rr_no' => ['nullable', 'string', 'max:255'],
            'vehicle_no' => ['nullable', 'string', 'max:50'],
            'terms_of_delivery' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'ship_same' => ['nullable'],
            'consignee_name' => ['nullable', 'string', 'max:255'],
            'consignee_address' => ['nullable', 'string'],
            'consignee_gstin' => ['nullable', 'string', 'max:15'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'items.*.uom' => ['nullable', 'string', 'max:20', 'exists:uoms,code'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'charges' => ['nullable', 'array'],
            'charges.*.charge_type_id' => ['nullable', 'exists:charge_types,id'],
            'charges.*.name' => ['nullable', 'string', 'max:255'],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0'],
            'charges.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    private function draftNumber(): string
    {
        do {
            $number = 'DRAFT-'.Str::upper(Str::random(6));
        } while (Invoice::withTrashed()->where('invoice_number', $number)->exists());

        return $number;
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultChargeRow(): array
    {
        $freight = ChargeType::where('code', 'FREIGHT')->first();

        return [
            'charge_type_id' => $freight?->id,
            'name' => $freight?->name ?? 'Freight',
            'amount' => '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'customers' => Customer::where('is_active', true)->with('addresses')->orderBy('legal_name')->get(),
            'products' => Product::where('is_active', true)->with(['taxRate', 'hsnSac', 'uom'])->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('code')->get(),
            'states' => State::where('is_active', true)->orderBy('name')->get(),
            'paymentTerms' => PaymentTerm::where('is_active', true)->orderBy('name')->get(),
            'chargeTypes' => ChargeType::where('is_active', true)->with(['taxRate', 'hsnSac'])->orderBy('name')->get(),
        ];
    }
}
