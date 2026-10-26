<?php

namespace App\Services;

class TaxCalculationService
{
    /**
     * @param  array<int, array{amount: float|string, tax_rate: float|string, hsn_sac?: string}>  $lines
     * @return array{
     *     tax_type: string,
     *     taxable_amount: float,
     *     cgst_amount: float,
     *     sgst_amount: float,
     *     igst_amount: float,
     *     tax_amount: float,
     *     round_off: float,
     *     grand_total: float,
     *     hsn_summary: array<int, array>
     * }
     */
    public function calculate(array $lines, string $sellerStateCode, string $placeOfSupplyCode, string $roundOffRule = 'nearest'): array
    {
        $isInterState = strtoupper(trim($sellerStateCode)) !== strtoupper(trim($placeOfSupplyCode));
        $taxType = $isInterState ? 'igst' : 'cgst_sgst';

        $taxable = 0.0;
        $cgst = 0.0;
        $sgst = 0.0;
        $igst = 0.0;
        $hsnBuckets = [];

        foreach ($lines as $line) {
            $amount = round((float) $line['amount'], 2);
            $rate = (float) ($line['tax_rate'] ?? 0);
            $hsn = $line['hsn_sac'] ?? 'NA';
            $taxable += $amount;

            $lineTax = round($amount * $rate / 100, 2);

            if ($isInterState) {
                $igst += $lineTax;
                $key = $hsn.'|'.$rate.'|igst';
                if (! isset($hsnBuckets[$key])) {
                    $hsnBuckets[$key] = [
                        'hsn_sac' => $hsn,
                        'taxable_value' => 0,
                        'tax_rate' => $rate,
                        'cgst_amount' => 0,
                        'sgst_amount' => 0,
                        'igst_amount' => 0,
                        'tax_amount' => 0,
                    ];
                }
                $hsnBuckets[$key]['taxable_value'] += $amount;
                $hsnBuckets[$key]['igst_amount'] += $lineTax;
                $hsnBuckets[$key]['tax_amount'] += $lineTax;
            } else {
                $half = round($lineTax / 2, 2);
                // Adjust for odd paisa so halves sum to total
                $otherHalf = round($lineTax - $half, 2);
                $cgst += $half;
                $sgst += $otherHalf;
                $key = $hsn.'|'.$rate.'|cgst';
                if (! isset($hsnBuckets[$key])) {
                    $hsnBuckets[$key] = [
                        'hsn_sac' => $hsn,
                        'taxable_value' => 0,
                        'tax_rate' => $rate,
                        'cgst_amount' => 0,
                        'sgst_amount' => 0,
                        'igst_amount' => 0,
                        'tax_amount' => 0,
                    ];
                }
                $hsnBuckets[$key]['taxable_value'] += $amount;
                $hsnBuckets[$key]['cgst_amount'] += $half;
                $hsnBuckets[$key]['sgst_amount'] += $otherHalf;
                $hsnBuckets[$key]['tax_amount'] += $lineTax;
            }
        }

        $taxable = round($taxable, 2);
        $cgst = round($cgst, 2);
        $sgst = round($sgst, 2);
        $igst = round($igst, 2);
        $taxAmount = round($cgst + $sgst + $igst, 2);
        $beforeRound = round($taxable + $taxAmount, 2);

        $grand = match ($roundOffRule) {
            'up' => ceil($beforeRound),
            'down' => floor($beforeRound),
            default => round($beforeRound, 0),
        };
        $roundOff = round($grand - $beforeRound, 2);

        return [
            'tax_type' => $taxType,
            'taxable_amount' => $taxable,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => $igst,
            'tax_amount' => $taxAmount,
            'round_off' => $roundOff,
            'grand_total' => (float) $grand,
            'hsn_summary' => array_values(array_map(function ($row) {
                $row['taxable_value'] = round($row['taxable_value'], 2);
                $row['cgst_amount'] = round($row['cgst_amount'], 2);
                $row['sgst_amount'] = round($row['sgst_amount'], 2);
                $row['igst_amount'] = round($row['igst_amount'], 2);
                $row['tax_amount'] = round($row['tax_amount'], 2);

                return $row;
            }, $hsnBuckets)),
        ];
    }

    public function lineAmount(float $qty, float $rate, float $discountPercent = 0, ?float $discountAmount = null): array
    {
        $gross = round($qty * $rate, 2);
        if ($discountAmount !== null && $discountPercent <= 0) {
            $disc = round($discountAmount, 2);
            $percent = $gross > 0 ? round(($disc / $gross) * 100, 4) : 0;
        } else {
            $percent = $discountPercent;
            $disc = round($gross * $percent / 100, 2);
        }
        $net = round($gross - $disc, 2);

        return [
            'gross' => $gross,
            'discount_percent' => $percent,
            'discount_amount' => $disc,
            'amount' => $net,
        ];
    }
}
