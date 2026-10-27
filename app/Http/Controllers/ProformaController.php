<?php

namespace App\Http\Controllers;

use App\Helpers\IndianFormat;
use App\Http\Controllers\Concerns\PreparesDocumentLines;
use App\Models\BankAccount;
use App\Models\ChargeType;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\Invoice;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\State;
use App\Models\Uom;
use App\Services\AmountInWords;
use App\Services\DocumentNumberService;
use App\Services\TaxCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ProformaController extends Controller
{
    use PreparesDocumentLines;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $proformas = Proforma::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('proforma_number', 'like', "%{$search}%")
                        ->orWhere('buyer_name', 'like', "%{$search}%")
                        ->orWhere('buyer_gstin', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('proforma_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('proformas.index', compact('proformas', 'search', 'status'));
    }

    public function create(Request $request): View|RedirectResponse
    {
        $availableQuotations = $this->availableQuotations();
        $selectedQuotationId = $request->integer('quotation_id') ?: null;
        $quotation = null;

        if ($selectedQuotationId) {
            $quotation = $availableQuotations->firstWhere('id', $selectedQuotationId);
            if (! $quotation) {
                return redirect()
                    ->route('proformas.create')
                    ->with('error', 'That quotation already has a proforma invoice or is not available.');
            }

            $quotation->load(['items', 'charges']);
        }

        $itemRows = [[
            'product_id' => '',
            'description' => '',
            'quantity' => 1,
            'uom' => '',
            'rate' => '',
            'discount_percent' => 0,
            'tax_rate' => '',
        ]];
        $chargeRows = [$this->defaultChargeRow()];

        if ($quotation) {
            $itemRows = $quotation->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all() ?: $itemRows;

            $mappedCharges = $quotation->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'amount' => $charge->amount,
                'tax_rate' => $charge->tax_rate,
            ])->all();
            $chargeRows = $mappedCharges !== [] ? $mappedCharges : $chargeRows;
        }

        return view('proformas.create', array_merge($this->lookups(), [
            'proforma' => null,
            'itemRows' => $itemRows,
            'chargeRows' => $chargeRows,
            'availableQuotations' => $availableQuotations,
            'selectedQuotationId' => $quotation?->id,
            'prefillCustomer' => $quotation?->customer_id ?? $request->query('customer_id'),
            'prefill' => [
                'place_of_supply_code' => $quotation?->place_of_supply_code,
                'payment_term_id' => $quotation?->payment_term_id,
                'valid_until' => optional($quotation?->valid_until)->toDateString(),
                'notes' => trim(($quotation?->subject ? $quotation->subject."\n" : '').($quotation?->notes ?? '')),
                'reference_no' => $quotation?->quotation_number,
                'reference_date' => optional($quotation?->quotation_date)->toDateString(),
            ],
        ]));
    }

    public function store(Request $request, TaxCalculationService $tax, DocumentNumberService $numbers): RedirectResponse
    {
        $proforma = $this->persist($request, $tax, $numbers, null);

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma invoice '.$proforma->proforma_number.' saved.');
    }

    public function show(Proforma $proforma): View
    {
        $proforma->load(['items.product', 'charges', 'taxSummary', 'customer', 'quotation', 'invoices']);

        return view('proformas.show', compact('proforma'));
    }

    public function edit(Proforma $proforma): View|RedirectResponse
    {
        if (! $proforma->isEditable()) {
            return redirect()
                ->route('proformas.show', $proforma)
                ->with('error', 'Only draft or sent proformas can be edited.');
        }

        $proforma->load(['items', 'charges', 'quotation']);

        return view('proformas.create', array_merge($this->lookups(), [
            'proforma' => $proforma,
            'itemRows' => $proforma->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all(),
            'chargeRows' => $proforma->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'amount' => $charge->amount,
            ])->all() ?: [$this->defaultChargeRow()],
            'availableQuotations' => $this->availableQuotations($proforma),
            'selectedQuotationId' => $proforma->quotation_id,
            'prefillCustomer' => null,
            'prefill' => [],
        ]));
    }

    public function update(Request $request, Proforma $proforma, TaxCalculationService $tax, DocumentNumberService $numbers): RedirectResponse
    {
        if (! $proforma->isEditable()) {
            return redirect()
                ->route('proformas.show', $proforma)
                ->with('error', 'Only draft or sent proformas can be edited.');
        }

        $proforma = $this->persist($request, $tax, $numbers, $proforma);

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma invoice updated.');
    }

    public function markSent(Proforma $proforma): RedirectResponse
    {
        if (! in_array($proforma->status, ['draft', 'sent'], true)) {
            return back()->with('error', 'This proforma cannot be marked as sent.');
        }

        $proforma->update([
            'status' => 'sent',
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Proforma marked as sent.');
    }

    public function cancel(Proforma $proforma): RedirectResponse
    {
        if (in_array($proforma->status, ['converted', 'cancelled'], true)) {
            return back()->with('error', 'This proforma cannot be cancelled.');
        }

        $proforma->update([
            'status' => 'cancelled',
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma cancelled.');
    }

    public function convertToInvoice(Proforma $proforma, TaxCalculationService $tax): RedirectResponse
    {
        if (in_array($proforma->status, ['cancelled', 'converted'], true)) {
            return back()->with('error', 'This proforma cannot be converted.');
        }

        $existing = Invoice::query()
            ->where('proforma_id', $proforma->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existing) {
            return redirect()
                ->route('invoices.show', $existing)
                ->with('warning', 'This proforma is already linked to invoice '.$existing->invoice_number.'.');
        }

        $proforma->load(['items', 'charges', 'customer']);

        $payload = [
            'customer_id' => $proforma->customer_id,
            'invoice_date' => now()->toDateString(),
            'place_of_supply_code' => $proforma->place_of_supply_code,
            'payment_term_id' => $proforma->payment_term_id,
            'quotation_id' => $proforma->quotation_id,
            'proforma_id' => $proforma->id,
            'reference_no' => $proforma->proforma_number,
            'reference_date' => optional($proforma->proforma_date)->toDateString(),
            'buyer_order_no' => $proforma->buyer_order_no,
            'buyer_order_date' => optional($proforma->buyer_order_date)->toDateString(),
            'destination' => $proforma->destination,
            'terms_of_delivery' => $proforma->terms_of_delivery,
            'notes' => $proforma->notes,
            'ship_same' => 1,
            'items' => $proforma->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all(),
            'charges' => $proforma->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'amount' => $charge->amount,
                'tax_rate' => $charge->tax_rate,
            ])->all(),
        ];

        $request = Request::create('/invoices', 'POST', $payload);
        $request->setUserResolver(fn () => auth()->user());

        $invoice = app(InvoiceController::class)->persist($request, $tax, null, true);

        $invoice->update(['proforma_id' => $proforma->id]);
        $proforma->update([
            'status' => 'converted',
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Draft tax invoice created from proforma '.$proforma->proforma_number.'. Issue after selecting serials if needed.');
    }

    public function pdf(Proforma $proforma): Response
    {
        $proforma->load(['items.product', 'charges', 'taxSummary', 'customer']);
        $company = $this->companyProfile()?->loadMissing('state');
        $bank = BankAccount::where('is_default', true)->first()
            ?? BankAccount::where('is_active', true)->first();

        $pdf = Pdf::loadView('pdf.proforma', [
            'proforma' => $proforma,
            'company' => $company,
            'bank' => $bank,
        ])->setPaper('a4')->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'dpi' => 96,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
        ]);

        $filename = preg_replace('/[\/\\\\]+/', '-', $proforma->proforma_number).'.pdf';

        return $pdf->stream($filename);
    }

    public function persist(
        Request $request,
        TaxCalculationService $tax,
        DocumentNumberService $numbers,
        ?Proforma $proforma,
        bool $validated = false,
    ): Proforma {
        $data = $validated ? $request->all() : $request->validate($this->rules($proforma));

        $quotation = Quotation::query()->findOrFail($data['quotation_id']);
        if ((int) $quotation->customer_id !== (int) $data['customer_id']) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer must match the selected quotation.',
            ]);
        }

        $taken = Proforma::query()
            ->where('quotation_id', $quotation->id)
            ->whereNotIn('status', ['cancelled'])
            ->when($proforma, fn ($query) => $query->where('id', '!=', $proforma->id))
            ->exists();

        if ($taken) {
            throw ValidationException::withMessages([
                'quotation_id' => 'A proforma invoice already exists for this quotation.',
            ]);
        }

        $data['reference_no'] = $data['reference_no'] ?? $quotation->quotation_number;
        $data['reference_date'] = $data['reference_date'] ?? optional($quotation->quotation_date)->toDateString();

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
        if ($company?->books_locked_upto && Carbon::parse($data['proforma_date'])->lte($company->books_locked_upto)) {
            throw ValidationException::withMessages([
                'proforma_date' => 'Books are locked up to '.IndianFormat::date($company->books_locked_upto).'.',
            ]);
        }

        $interState = strtoupper(trim($seller)) !== strtoupper(trim($pos));
        $prepared = $this->prepareLines($data['items'] ?? [], $data['charges'] ?? [], $tax, $interState);
        $totals = $tax->calculate($prepared['tax_lines'], $seller, $pos, $this->roundOffRule());

        $term = ! empty($data['payment_term_id'])
            ? PaymentTerm::find($data['payment_term_id'])
            : ($customer->payment_term_id ? PaymentTerm::find($customer->payment_term_id) : null);
        $dueDate = $data['due_date'] ?? null;
        if (! $dueDate && $term) {
            $dueDate = Carbon::parse($data['proforma_date'])->addDays((int) $term->due_days)->toDateString();
        }

        $validUntil = $data['valid_until'] ?? null;
        if (! $validUntil) {
            $days = (int) ($company?->quotation_validity_days ?: 7);
            $validUntil = Carbon::parse($data['proforma_date'])->addDays($days)->toDateString();
        }

        return DB::transaction(function () use ($proforma, $data, $customer, $context, $prepared, $totals, $term, $dueDate, $pos, $numbers, $validUntil) {
            $shipSame = filter_var($data['ship_same'] ?? true, FILTER_VALIDATE_BOOLEAN);
            $attributes = [
                'proforma_date' => $data['proforma_date'],
                'valid_until' => $validUntil,
                'financial_year_id' => FinancialYear::where('is_current', true)->value('id'),
                'quotation_id' => $data['quotation_id'] ?? $proforma?->quotation_id,
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
                'destination' => $data['destination'] ?? null,
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
                'amount_in_words' => AmountInWords::convert($totals['grand_total']),
                'tax_amount_in_words' => AmountInWords::taxAmount($totals['tax_amount']),
                'supply_type' => $customer->gstin ? 'B2B' : 'B2C',
                'notes' => $data['notes'] ?? null,
                'updated_by' => auth()->id(),
            ];

            if ($proforma) {
                $proforma->update($attributes);
            } else {
                $proforma = Proforma::create([
                    ...$attributes,
                    'proforma_number' => $numbers->next('proforma'),
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }

            $proforma->items()->delete();
            $proforma->charges()->delete();
            $proforma->taxSummary()->delete();

            foreach ($prepared['items'] as $index => $item) {
                $proforma->items()->create([
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
                $proforma->charges()->create([
                    'charge_type_id' => $charge['charge_type_id'],
                    'name' => $charge['name'],
                    'hsn_sac' => $charge['hsn_sac'],
                    'amount' => $charge['amount'],
                    'tax_rate' => $charge['tax_rate'],
                    'tax_amount' => $charge['tax_amount'],
                ]);
            }

            foreach ($totals['hsn_summary'] as $row) {
                $proforma->taxSummary()->create($row);
            }

            return $proforma->fresh(['items', 'charges', 'taxSummary']);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Proforma $proforma = null): array
    {
        return [
            'customer_id' => ['required', 'exists:customers,id'],
            'proforma_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'place_of_supply_code' => ['required', 'exists:states,code'],
            'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
            'due_date' => ['nullable', 'date'],
            'quotation_id' => [
                'required',
                'exists:quotations,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($proforma): void {
                    $taken = Proforma::query()
                        ->where('quotation_id', $value)
                        ->whereNotIn('status', ['cancelled'])
                        ->when($proforma, fn ($query) => $query->where('id', '!=', $proforma->id))
                        ->exists();

                    if ($taken) {
                        $fail('A proforma invoice already exists for this quotation.');
                    }
                },
            ],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'reference_date' => ['nullable', 'date'],
            'buyer_order_no' => ['nullable', 'string', 'max:255'],
            'buyer_order_date' => ['nullable', 'date'],
            'destination' => ['nullable', 'string', 'max:255'],
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

    /**
     * Quotations that can still receive a new proforma (or the one already linked to this draft).
     *
     * @return Collection<int, Quotation>
     */
    private function availableQuotations(?Proforma $proforma = null): Collection
    {
        return Quotation::query()
            ->with('customer:id,customer_code,legal_name,trade_name')
            ->whereNotIn('status', ['rejected', 'expired'])
            ->where(function ($query) use ($proforma) {
                $query->whereDoesntHave('proformas', function ($proformas) {
                    $proformas->whereNotIn('status', ['cancelled']);
                });

                if ($proforma?->quotation_id) {
                    $query->orWhere('id', $proforma->quotation_id);
                }
            })
            ->latest('quotation_date')
            ->latest('id')
            ->get(['id', 'quotation_number', 'quotation_date', 'customer_id', 'customer_name', 'place_of_supply_code', 'payment_term_id', 'valid_until', 'subject', 'notes', 'status']);
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
