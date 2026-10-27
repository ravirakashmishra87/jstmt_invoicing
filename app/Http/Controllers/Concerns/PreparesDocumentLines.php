<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ChargeType;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\Product;
use App\Models\State;
use App\Services\TaxCalculationService;
use App\Support\RichText;
use Illuminate\Validation\ValidationException;

trait PreparesDocumentLines
{
    protected function companyProfile(): ?CompanyProfile
    {
        return once(fn () => CompanyProfile::query()->first());
    }

    protected function sellerStateCode(): string
    {
        return (string) ($this->companyProfile()?->state_code ?: '24');
    }

    protected function roundOffRule(): string
    {
        return (string) ($this->companyProfile()?->round_off_rule ?: 'nearest');
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, array<string, mixed>>  $charges
     * @return array{items: array<int, array<string, mixed>>, charges: array<int, array<string, mixed>>, tax_lines: array<int, array<string, mixed>>, discount_total: float, gross_total: float}
     */
    protected function prepareLines(array $items, array $charges, TaxCalculationService $tax, bool $interState): array
    {
        $preparedItems = [];
        $taxLines = [];
        $discountTotal = 0.0;
        $grossTotal = 0.0;

        foreach (array_values($items) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $productId = $row['product_id'] ?? null;
            $description = RichText::plain((string) ($row['description'] ?? ''));
            $qtyRaw = $row['quantity'] ?? null;
            $rateRaw = $row['rate'] ?? null;
            $blank = ($productId === null || $productId === '')
                && $description === ''
                && ($qtyRaw === null || $qtyRaw === '')
                && ($rateRaw === null || $rateRaw === '');

            if ($blank) {
                continue;
            }

            $product = $productId ? Product::with(['hsnSac', 'taxRate', 'uom'])->find($productId) : null;
            if (! $product && $description === '') {
                throw ValidationException::withMessages([
                    'items' => 'Each line needs a product or a description.',
                ]);
            }

            $qty = (float) ($qtyRaw === null || $qtyRaw === '' ? 1 : $qtyRaw);
            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Line quantity must be greater than zero.',
                ]);
            }

            $rate = ($rateRaw === null || $rateRaw === '')
                ? (float) ($product?->list_price ?? 0)
                : (float) $rateRaw;
            $discountPercent = (float) ($row['discount_percent'] ?? 0);
            $calc = $tax->lineAmount($qty, $rate, $discountPercent);

            $postedTax = $row['tax_rate'] ?? null;
            $taxRate = ($postedTax === null || $postedTax === '')
                ? (float) ($product?->taxRate?->rate ?? 0)
                : (float) $postedTax;

            $lineTax = round($calc['amount'] * $taxRate / 100, 2);
            $cgst = 0.0;
            $sgst = 0.0;
            $igst = 0.0;
            $cgstRate = 0.0;
            $sgstRate = 0.0;
            $igstRate = 0.0;

            if ($interState) {
                $igst = $lineTax;
                $igstRate = $taxRate;
            } else {
                $cgst = round($lineTax / 2, 2);
                $sgst = round($lineTax - $cgst, 2);
                $cgstRate = $taxRate / 2;
                $sgstRate = $taxRate / 2;
            }

            $hsn = $product?->hsnSac?->code ?: 'NA';
            $name = $product?->name ?: ($description !== '' ? $description : 'Item');
            $itemCode = $product?->sku;
            $lineDescription = $description !== ''
                ? $description
                : RichText::plain((string) ($product?->description ?? ''));
            $postedUom = trim((string) ($row['uom'] ?? ''));
            $uom = $postedUom !== '' ? $postedUom : ($product?->uom?->code);

            $preparedItems[] = [
                'product_id' => $product?->id,
                'item_code' => $itemCode,
                'product_name' => $name,
                'model_number' => $product?->model_number,
                'description' => $lineDescription,
                'secondary_description' => $product?->model_number,
                'hsn_sac' => $hsn === 'NA' ? null : $hsn,
                'uom' => $uom,
                'quantity' => $qty,
                'list_price' => (float) ($product?->list_price ?? $rate),
                'discount_percent' => $calc['discount_percent'],
                'discount_amount' => $calc['discount_amount'],
                'rate' => $rate,
                'amount' => $calc['amount'],
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
                'cgst_rate' => $cgstRate,
                'sgst_rate' => $sgstRate,
                'igst_rate' => $igstRate,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => $igst,
                'is_service' => ($product?->product_type ?? 'goods') === 'service',
                'track_serial' => (bool) ($product?->track_serial ?? false),
            ];

            $taxLines[] = [
                'amount' => $calc['amount'],
                'tax_rate' => $taxRate,
                'hsn_sac' => $hsn,
            ];
            $discountTotal += $calc['discount_amount'];
            $grossTotal += $calc['gross'];
        }

        if ($preparedItems === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one line item.',
            ]);
        }

        $preparedCharges = [];
        foreach (array_values($charges) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $amountRaw = $row['amount'] ?? null;
            $rateRaw = $row['rate'] ?? null;
            $name = trim((string) ($row['name'] ?? ''));
            $typeId = $row['charge_type_id'] ?? null;
            $atActual = filter_var($row['is_at_actual'] ?? false, FILTER_VALIDATE_BOOLEAN);
            $qtyRaw = $row['quantity'] ?? null;
            $postedUom = trim((string) ($row['uom'] ?? ''));

            $quantity = ($qtyRaw === null || $qtyRaw === '') ? 1.0 : (float) $qtyRaw;
            if ($quantity <= 0) {
                $quantity = 1.0;
            }

            $hasRate = ! ($rateRaw === null || $rateRaw === '');
            $rate = $hasRate ? (float) $rateRaw : 0.0;
            if ($hasRate) {
                $amount = round($quantity * $rate, 2);
            } else {
                $amount = ($amountRaw === null || $amountRaw === '') ? 0.0 : round((float) $amountRaw, 2);
                $rate = $quantity > 0 ? round($amount / $quantity, 4) : $amount;
            }

            if ($amount == 0.0 && ! $atActual && ! $hasRate) {
                continue;
            }
            if ($hasRate && $rate == 0.0 && ! $atActual) {
                continue;
            }

            $type = $typeId ? ChargeType::with(['hsnSac', 'taxRate'])->find($typeId) : null;
            $postedTax = $row['tax_rate'] ?? null;

            if ($type && ! $type->is_taxable) {
                $taxRate = 0.0;
            } elseif ($postedTax === null || $postedTax === '') {
                $taxRate = ($type && $type->is_taxable) ? (float) ($type->taxRate?->rate ?? 0) : 0.0;
            } else {
                $taxRate = (float) $postedTax;
            }

            $lineTax = round($amount * $taxRate / 100, 2);
            $hsn = $type?->hsnSac?->code;
            $chargeName = $name !== '' ? $name : ($type->name ?? 'Charge');

            $preparedCharges[] = [
                'charge_type_id' => $type?->id,
                'name' => $chargeName,
                'quantity' => $quantity,
                'uom' => $postedUom !== '' ? $postedUom : null,
                'rate' => $rate,
                'hsn_sac' => $hsn,
                'is_at_actual' => $atActual,
                'at_actual_text' => $row['at_actual_text'] ?? ($atActual ? 'At actual' : null),
                'percent_of_invoice' => ($row['percent_of_invoice'] ?? null) !== '' ? ($row['percent_of_invoice'] ?? null) : null,
                'amount' => $amount,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
            ];

            if ($amount > 0) {
                $taxLines[] = [
                    'amount' => $amount,
                    'tax_rate' => $taxRate,
                    'hsn_sac' => $hsn ?: 'NA',
                ];
                $grossTotal += $amount;
            }
        }

        return [
            'items' => $preparedItems,
            'charges' => $preparedCharges,
            'tax_lines' => $taxLines,
            'discount_total' => round($discountTotal, 2),
            'gross_total' => round($grossTotal, 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function customerContext(Customer $customer, ?string $posCode): array
    {
        $customer->loadMissing(['addresses.state']);

        $billing = $customer->addresses->first(fn ($address) => $address->type === 'billing' && $address->is_default)
            ?? $customer->addresses->firstWhere('type', 'billing')
            ?? $customer->addresses->first();

        $shipping = $customer->addresses->first(fn ($address) => $address->type === 'shipping' && $address->is_default)
            ?? $customer->addresses->firstWhere('type', 'shipping')
            ?? $billing;

        $posCode = $posCode ?: ($billing->state_code ?? $shipping->state_code ?? '');
        $posState = $posCode !== '' ? State::where('code', $posCode)->first() : null;

        return [
            'billing' => $billing,
            'shipping' => $shipping,
            'billing_address' => $billing?->fullAddress() ?? '',
            'shipping_address' => $shipping?->fullAddress() ?? '',
            'billing_state' => $billing?->state?->name,
            'billing_state_code' => $billing?->state_code,
            'shipping_state' => $shipping?->state?->name,
            'shipping_state_code' => $shipping?->state_code,
            'pos_code' => $posCode,
            'pos_name' => $posState?->name ?? ($billing?->state?->name ?? ''),
        ];
    }
}
