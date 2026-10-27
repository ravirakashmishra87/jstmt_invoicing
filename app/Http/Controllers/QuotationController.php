<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\PreparesDocumentLines;
use App\Models\BankAccount;
use App\Models\ChargeType;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentTerm;
use App\Models\Product;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\State;
use App\Models\TermsTemplate;
use App\Models\Uom;
use App\Services\AmountInWords;
use App\Services\DocumentNumberService;
use App\Services\TaxCalculationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class QuotationController extends Controller
{
    use PreparesDocumentLines;

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $quotations = Quotation::query()
            ->with('customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('quotation_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('quotation_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', compact('quotations', 'search', 'status'));
    }

    public function create(): View
    {
        return view('quotations.create', array_merge($this->lookups(), [
            'quotation' => null,
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
        ]));
    }

    public function store(Request $request, TaxCalculationService $tax, DocumentNumberService $numbers): RedirectResponse
    {
        $quotation = $this->persist($request, $tax, $numbers, null);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation '.$quotation->quotation_number.' saved.');
    }

    public function show(Quotation $quotation): View
    {
        $quotation->load(['items.product', 'charges', 'customer', 'salesperson']);

        return view('quotations.show', [
            'quotation' => $quotation,
            'amountInWords' => AmountInWords::convert($quotation->grand_total),
            'hasInvoice' => Invoice::where('quotation_id', $quotation->id)->where('status', '!=', 'cancelled')->exists(),
            'hasProforma' => Proforma::where('quotation_id', $quotation->id)->whereNotIn('status', ['cancelled'])->exists(),
        ]);
    }

    public function edit(Quotation $quotation): View
    {
        $quotation->load(['items', 'charges']);

        return view('quotations.edit', array_merge($this->lookups(), [
            'quotation' => $quotation,
            'itemRows' => $quotation->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all(),
            'chargeRows' => $quotation->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'quantity' => $charge->quantity,
                'uom' => $charge->uom,
                'rate' => $charge->rate,
                'amount' => $charge->amount,
                'is_at_actual' => $charge->is_at_actual,
            ])->all() ?: [$this->defaultChargeRow()],
        ]));
    }

    public function update(Request $request, Quotation $quotation, TaxCalculationService $tax, DocumentNumberService $numbers): RedirectResponse
    {
        $this->persist($request, $tax, $numbers, $quotation);

        return redirect()
            ->route('quotations.show', $quotation)
            ->with('success', 'Quotation updated.');
    }

    public function updateStatus(Request $request, Quotation $quotation): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:draft,sent,under_negotiation,accepted,rejected,expired,revised'],
            'win_loss_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $quotation->update([
            ...$data,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Quotation status updated.');
    }

    public function duplicate(Quotation $quotation, DocumentNumberService $numbers): RedirectResponse
    {
        $copy = DB::transaction(function () use ($quotation, $numbers) {
            $quotation->load(['items.product', 'charges']);
            $modelCode = $this->modelCode($quotation->items->first()?->product);

            $copy = $quotation->replicate([
                'quotation_number', 'status', 'win_loss_reason', 'created_by', 'updated_by',
            ]);
            $copy->quotation_number = $numbers->next('quotation', ['MODELCODE' => $modelCode]);
            $copy->revision = (int) $quotation->revision + 1;
            $copy->parent_quotation_id = $quotation->id;
            $copy->quotation_date = now()->toDateString();
            $copy->valid_until = now()->addDays((int) ($this->companyProfile()?->quotation_validity_days ?? 7))->toDateString();
            $copy->status = 'draft';
            $copy->win_loss_reason = null;
            $copy->created_by = auth()->id();
            $copy->updated_by = auth()->id();
            $copy->save();

            foreach ($quotation->items as $item) {
                $copy->items()->create($item->only([
                    'sr_no', 'product_id', 'product_name', 'model_number', 'description', 'hsn_sac', 'uom',
                    'quantity', 'list_price', 'discount_percent', 'discount_amount', 'rate', 'amount',
                    'tax_rate', 'tax_amount',
                ]));
            }
            foreach ($quotation->charges as $charge) {
                $copy->charges()->create($charge->only([
                    'charge_type_id', 'name', 'quantity', 'uom', 'rate', 'hsn_sac', 'is_at_actual', 'at_actual_text',
                    'percent_of_invoice', 'amount', 'tax_rate', 'tax_amount',
                ]));
            }

            return $copy;
        });

        return redirect()
            ->route('quotations.edit', $copy)
            ->with('success', 'Quotation duplicated as '.$copy->quotation_number.'.');
    }

    public function convertToProforma(Quotation $quotation, TaxCalculationService $tax, DocumentNumberService $numbers): RedirectResponse
    {
        if (in_array($quotation->status, ['rejected', 'expired'], true)) {
            return back()->with('error', 'Rejected or expired quotations cannot be converted.');
        }

        $existing = Proforma::query()
            ->where('quotation_id', $quotation->id)
            ->whereNotIn('status', ['cancelled'])
            ->first();

        if ($existing) {
            return redirect()
                ->route('proformas.show', $existing)
                ->with('warning', 'This quotation is already linked to proforma '.$existing->proforma_number.'.');
        }

        $quotation->load(['items', 'charges', 'customer']);

        $payload = [
            'customer_id' => $quotation->customer_id,
            'proforma_date' => now()->toDateString(),
            'place_of_supply_code' => $quotation->place_of_supply_code,
            'payment_term_id' => $quotation->payment_term_id,
            'quotation_id' => $quotation->id,
            'reference_no' => $quotation->quotation_number,
            'reference_date' => optional($quotation->quotation_date)->toDateString(),
            'notes' => trim(($quotation->subject ? $quotation->subject."\n" : '').($quotation->notes ?? '')),
            'ship_same' => 1,
            'items' => $quotation->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all(),
            'charges' => $quotation->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'amount' => $charge->amount,
                'tax_rate' => $charge->tax_rate,
            ])->all(),
        ];

        $request = Request::create('/proformas', 'POST', $payload);
        $request->setUserResolver(fn () => auth()->user());

        $proforma = app(ProformaController::class)->persist($request, $tax, $numbers, null, true);

        return redirect()
            ->route('proformas.show', $proforma)
            ->with('success', 'Proforma '.$proforma->proforma_number.' created from quotation '.$quotation->quotation_number.'.');
    }

    public function convertToInvoice(Quotation $quotation): RedirectResponse
    {
        if (in_array($quotation->status, ['rejected', 'expired'], true)) {
            return back()->with('error', 'Rejected or expired quotations cannot be converted.');
        }

        $existing = Invoice::where('quotation_id', $quotation->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existing) {
            return redirect()
                ->route('invoices.show', $existing)
                ->with('warning', 'This quotation is already linked to invoice '.$existing->invoice_number.'.');
        }

        $quotation->load(['items', 'charges', 'customer']);

        $payload = [
            'customer_id' => $quotation->customer_id,
            'invoice_date' => now()->toDateString(),
            'place_of_supply_code' => $quotation->place_of_supply_code,
            'payment_term_id' => $quotation->payment_term_id,
            'quotation_id' => $quotation->id,
            'reference_no' => $quotation->quotation_number,
            'reference_date' => optional($quotation->quotation_date)->toDateString(),
            'notes' => trim(($quotation->subject ? $quotation->subject."\n" : '').($quotation->notes ?? '')),
            'items' => $quotation->items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'rate' => $item->rate,
                'discount_percent' => $item->discount_percent,
                'tax_rate' => $item->tax_rate,
            ])->all(),
            'charges' => $quotation->charges->map(fn ($charge) => [
                'charge_type_id' => $charge->charge_type_id,
                'name' => $charge->name,
                'quantity' => $charge->quantity,
                'uom' => $charge->uom,
                'rate' => $charge->rate,
                'amount' => $charge->amount,
                'tax_rate' => $charge->tax_rate,
            ])->all(),
        ];

        $request = Request::create('/invoices', 'POST', $payload);
        $request->setUserResolver(fn () => auth()->user());

        $invoice = app(InvoiceController::class)->persist(
            $request,
            app(TaxCalculationService::class),
            null,
            true,
        );

        $quotation->update([
            'status' => 'accepted',
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Draft invoice '.$invoice->invoice_number.' created. Issue it after selecting serials.');
    }

    public function pdf(Quotation $quotation): Response
    {
        $quotation->load([
            'items.product.images',
            'items.product.specGroups.items',
            'items.product.components',
            'charges',
            'customer',
            'salesperson',
        ]);
        $company = $this->companyProfile()?->loadMissing('state');
        $bank = BankAccount::where('is_default', true)->first()
            ?? BankAccount::where('is_active', true)->first();

        $specGroups = [];
        $components = [];
        $productImages = [];
        foreach ($quotation->items as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }
            if ($quotation->show_images) {
                $absolute = $product->primaryImageAbsolutePath();
                if ($absolute) {
                    $productImages[$product->id] = $absolute;
                }
            }
            foreach ($product->specGroups as $group) {
                $key = strtolower(trim($group->name));
                if (! isset($specGroups[$key])) {
                    $specGroups[$key] = [
                        'name' => $group->name,
                        'items' => [],
                    ];
                }
                foreach ($group->items as $specItem) {
                    $specGroups[$key]['items'][] = [
                        'label' => (string) ($specItem->label ?? ''),
                        'value' => (string) $specItem->value,
                    ];
                }
            }
            foreach ($product->components as $component) {
                $components[] = [
                    'component_name' => $component->component_name,
                    'brand_name' => $component->brand_name,
                ];
            }
        }

        $rates = $quotation->items->pluck('tax_rate')->filter(fn ($rate) => (float) $rate > 0);
        $gstRate = (float) ($rates->avg() ?: 18);
        $gstLabel = rtrim(rtrim(number_format($gstRate, 2, '.', ''), '0'), '.');
        $halfGstLabel = rtrim(rtrim(number_format($gstRate / 2, 2, '.', ''), '0'), '.');

        $pdf = Pdf::loadView('pdf.quotation', [
            'quotation' => $quotation,
            'company' => $company,
            'bank' => $bank,
            'amountInWords' => AmountInWords::convert($quotation->grand_total),
            'specGroups' => array_values($specGroups),
            'components' => $components,
            'productImages' => array_values($productImages),
            'termsRows' => $this->parseTermsRows($quotation->terms_content),
            'gstLabel' => $gstLabel,
            'halfGstLabel' => $halfGstLabel,
        ])->setPaper('a4');

        $filename = preg_replace('/[\/\\\\]+/', '-', $quotation->quotation_number).'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * @return array<int, array{title: string, content: string}>
     */
    private function parseTermsRows(?string $content): array
    {
        $content = trim((string) $content);
        if ($content === '') {
            return [];
        }

        $rows = [];
        foreach (preg_split("/\r\n|\n|\r/", $content) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $line = preg_replace('/^\d+[\.\)]\s*/', '', $line) ?? $line;
            if (preg_match('/^([^:]{2,40}):\s*(.+)$/u', $line, $matches)) {
                $rows[] = [
                    'title' => trim($matches[1]),
                    'content' => trim($matches[2]),
                ];
            } else {
                $rows[] = [
                    'title' => 'Note',
                    'content' => $line,
                ];
            }
        }

        return $rows;
    }

    private function persist(Request $request, TaxCalculationService $tax, DocumentNumberService $numbers, ?Quotation $quotation): Quotation
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date'],
            'subject' => ['nullable', 'string', 'max:255'],
            'payment_term_id' => ['nullable', 'exists:payment_terms,id'],
            'place_of_supply_code' => ['required', 'exists:states,code'],
            'notes' => ['nullable', 'string'],
            'terms_content' => ['nullable', 'string'],
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
            'charges.*.quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'charges.*.uom' => ['nullable', 'string', 'max:20', 'exists:uoms,code'],
            'charges.*.rate' => ['nullable', 'numeric', 'min:0'],
            'charges.*.amount' => ['nullable', 'numeric', 'min:0'],
            'charges.*.is_at_actual' => ['nullable'],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $context = $this->customerContext($customer, $data['place_of_supply_code']);
        $seller = $this->sellerStateCode();
        $interState = strtoupper(trim($seller)) !== strtoupper(trim((string) $context['pos_code']));
        $prepared = $this->prepareLines($data['items'], $data['charges'] ?? [], $tax, $interState);
        $totals = $tax->calculate($prepared['tax_lines'], $seller, (string) $context['pos_code'], $this->roundOffRule());

        $validUntil = $data['valid_until'] ?? null;
        if (! $validUntil) {
            $days = (int) ($this->companyProfile()?->quotation_validity_days ?? 7);
            $validUntil = Carbon::parse($data['quotation_date'])->addDays($days)->toDateString();
        }

        return DB::transaction(function () use ($quotation, $data, $customer, $context, $prepared, $totals, $validUntil, $numbers, $request) {
            $attributes = [
                'quotation_date' => $data['quotation_date'],
                'valid_until' => $validUntil,
                'customer_id' => $customer->id,
                'customer_name' => $customer->trade_name ?: $customer->legal_name,
                'customer_gstin' => $customer->gstin,
                'customer_address' => $context['billing_address'],
                'customer_mobile' => $customer->mobile,
                'customer_email' => $customer->email,
                'subject' => $data['subject'] ?? null,
                'salesperson_id' => $customer->salesperson_id ?: auth()->id(),
                'payment_term_id' => $data['payment_term_id'] ?? $customer->payment_term_id,
                'place_of_supply_state' => $context['pos_name'],
                'place_of_supply_code' => $context['pos_code'],
                'tax_type' => $totals['tax_type'],
                'subtotal' => $prepared['gross_total'],
                'discount_amount' => $prepared['discount_total'],
                'taxable_amount' => $totals['taxable_amount'],
                'cgst_amount' => $totals['cgst_amount'],
                'sgst_amount' => $totals['sgst_amount'],
                'igst_amount' => $totals['igst_amount'],
                'round_off' => $totals['round_off'],
                'grand_total' => $totals['grand_total'],
                'terms_content' => $data['terms_content']
                    ?? $quotation?->terms_content
                    ?? TermsTemplate::where('document_type', 'quotation')->where('is_default', true)->value('content'),
                'show_images' => $request->boolean('show_images', true),
                'show_bank_details' => $request->boolean('show_bank_details', true),
                'notes' => $data['notes'] ?? null,
                'updated_by' => auth()->id(),
            ];

            if ($quotation) {
                $quotation->update($attributes);
            } else {
                $firstProduct = Product::find($prepared['items'][0]['product_id'] ?? null);
                $quotation = Quotation::create([
                    ...$attributes,
                    'quotation_number' => $numbers->next('quotation', ['MODELCODE' => $this->modelCode($firstProduct)]),
                    'revision' => 0,
                    'status' => 'draft',
                    'created_by' => auth()->id(),
                ]);
            }

            $quotation->items()->delete();
            foreach ($prepared['items'] as $index => $item) {
                $quotation->items()->create([
                    'sr_no' => $index + 1,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'model_number' => $item['model_number'],
                    'description' => $item['description'],
                    'hsn_sac' => $item['hsn_sac'],
                    'uom' => $item['uom'],
                    'quantity' => $item['quantity'],
                    'list_price' => $item['list_price'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                    'rate' => $item['rate'],
                    'amount' => $item['amount'],
                    'tax_rate' => $item['tax_rate'],
                    'tax_amount' => $item['tax_amount'],
                ]);
            }

            $quotation->charges()->delete();
            foreach ($prepared['charges'] as $charge) {
                $quotation->charges()->create($charge);
            }

            return $quotation;
        });
    }

    private function modelCode(?Product $product): string
    {
        $raw = $product?->model_number ?: 'QT';
        $code = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        return $code !== '' ? $code : 'QT';
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
            'quantity' => 1,
            'uom' => '',
            'rate' => '',
            'amount' => '',
            'is_at_actual' => false,
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
            'chargeTypes' => ChargeType::where('is_active', true)->with('taxRate')->orderBy('name')->get(),
            'defaultTerms' => TermsTemplate::where('document_type', 'quotation')->where('is_default', true)->value('content'),
        ];
    }
}
