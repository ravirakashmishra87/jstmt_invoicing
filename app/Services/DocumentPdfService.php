<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\CompanyProfile;
use App\Models\Invoice;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use RuntimeException;

class DocumentPdfService
{
    /**
     * @return array{binary: string, filename: string}
     */
    public function forType(string $type, int $id): array
    {
        return match ($type) {
            'quotation' => $this->quotation(Quotation::findOrFail($id)),
            'proforma' => $this->proforma(Proforma::findOrFail($id)),
            'invoice' => $this->invoice(Invoice::findOrFail($id)),
            'receipt' => $this->receipt(Receipt::findOrFail($id)),
            default => throw new RuntimeException('Unsupported document type.'),
        };
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public function quotation(Quotation $quotation): array
    {
        $quotation->load([
            'items.product.images',
            'items.product.specGroups.items',
            'items.product.components',
            'charges',
            'customer',
            'salesperson',
        ]);
        $company = CompanyProfile::with('state')->first();
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

        return [
            'binary' => $pdf->output(),
            'filename' => preg_replace('/[\/\\\\]+/', '-', $quotation->quotation_number).'.pdf',
        ];
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public function proforma(Proforma $proforma): array
    {
        $proforma->load(['items.product', 'charges', 'taxSummary', 'customer']);
        $company = CompanyProfile::with('state')->first();
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

        return [
            'binary' => $pdf->output(),
            'filename' => preg_replace('/[\/\\\\]+/', '-', $proforma->proforma_number).'.pdf',
        ];
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public function invoice(Invoice $invoice): array
    {
        $invoice->load(['items.serials', 'items.product', 'charges', 'taxSummary', 'customer']);
        $company = CompanyProfile::with('state')->first();
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

        return [
            'binary' => $pdf->output(),
            'filename' => preg_replace('/[\/\\\\]+/', '-', $invoice->invoice_number).'.pdf',
        ];
    }

    /**
     * @return array{binary: string, filename: string}
     */
    public function receipt(Receipt $receipt): array
    {
        $receipt->load(['customer', 'paymentMode', 'bankAccount', 'allocations.invoice']);
        $company = CompanyProfile::with('state')->first();

        $pdf = Pdf::loadView('pdf.receipt', [
            'receipt' => $receipt,
            'company' => $company,
            'amountInWords' => AmountInWords::convert($receipt->amount),
        ])->setPaper('a4');

        return [
            'binary' => $pdf->output(),
            'filename' => preg_replace('/[\/\\\\]+/', '-', $receipt->receipt_number).'.pdf',
        ];
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
}
