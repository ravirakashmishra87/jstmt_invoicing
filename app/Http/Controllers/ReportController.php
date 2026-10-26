<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceTaxSummary;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function salesRegister(Request $request): View|StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = Invoice::query()
            ->with('customer')
            ->whereBetween('invoice_date', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->orderBy('invoice_date')
            ->orderBy('id')
            ->get();

        if ($request->query('export') === 'csv') {
            $this->ensureExport();

            return $this->csv('sales-register.csv', [
                'Invoice No', 'Date', 'Customer', 'GSTIN', 'Status', 'Taxable', 'CGST', 'SGST', 'IGST', 'Round Off', 'Grand Total', 'Balance Due',
            ], $rows->map(fn (Invoice $invoice) => [
                $invoice->invoice_number,
                optional($invoice->invoice_date)->format('Y-m-d'),
                $invoice->buyer_name,
                $invoice->buyer_gstin,
                $invoice->status,
                $invoice->taxable_amount,
                $invoice->cgst_amount,
                $invoice->sgst_amount,
                $invoice->igst_amount,
                $invoice->round_off,
                $invoice->grand_total,
                $invoice->balance_due,
            ]));
        }

        return view('reports.sales', compact('rows', 'from', 'to'));
    }

    public function stockSummary(Request $request): View|StreamedResponse
    {
        $products = Product::query()
            ->with(['uom', 'stockBalances'])
            ->where('product_type', 'goods')
            ->orderBy('name')
            ->get();

        if ($request->query('export') === 'csv') {
            $this->ensureExport();

            return $this->csv('stock-summary.csv', [
                'SKU', 'Product', 'UoM', 'On Hand', 'Reserved', 'Available', 'Avg Cost', 'Stock Value', 'Reorder Level',
            ], $products->map(function (Product $product) {
                $onHand = (float) $product->stockBalances->sum('quantity_on_hand');
                $reserved = (float) $product->stockBalances->sum('quantity_reserved');
                $avg = (float) ($product->stockBalances->sortByDesc('quantity_on_hand')->first()->average_cost ?? 0);

                return [
                    $product->sku,
                    $product->name,
                    $product->uom?->code,
                    $onHand,
                    $reserved,
                    $onHand - $reserved,
                    $avg,
                    round($onHand * $avg, 2),
                    $product->reorder_level,
                ];
            }));
        }

        return view('reports.stock', compact('products'));
    }

    public function outstanding(Request $request): View|StreamedResponse
    {
        $rows = Invoice::query()
            ->with('customer')
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('balance_due', '>', 0)
            ->orderBy('due_date')
            ->orderBy('invoice_date')
            ->get();

        if ($request->query('export') === 'csv') {
            $this->ensureExport();

            return $this->csv('outstanding.csv', [
                'Invoice No', 'Date', 'Due Date', 'Customer', 'Grand Total', 'Paid', 'Balance', 'Days Overdue',
            ], $rows->map(function (Invoice $invoice) {
                $overdue = ($invoice->due_date && $invoice->due_date->copy()->endOfDay()->isPast())
                    ? (int) $invoice->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay())
                    : 0;

                return [
                    $invoice->invoice_number,
                    optional($invoice->invoice_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    $invoice->buyer_name,
                    $invoice->grand_total,
                    $invoice->amount_paid,
                    $invoice->balance_due,
                    $overdue,
                ];
            }));
        }

        return view('reports.outstanding', compact('rows'));
    }

    public function gstSummary(Request $request): View|StreamedResponse
    {
        [$from, $to] = $this->range($request);

        $rows = InvoiceTaxSummary::query()
            ->select([
                'invoice_tax_summary.hsn_sac',
                'invoice_tax_summary.tax_rate',
                DB::raw('SUM(invoice_tax_summary.taxable_value) as taxable_value'),
                DB::raw('SUM(invoice_tax_summary.cgst_amount) as cgst_amount'),
                DB::raw('SUM(invoice_tax_summary.sgst_amount) as sgst_amount'),
                DB::raw('SUM(invoice_tax_summary.igst_amount) as igst_amount'),
                DB::raw('SUM(invoice_tax_summary.tax_amount) as tax_amount'),
            ])
            ->join('invoices', 'invoices.id', '=', 'invoice_tax_summary.invoice_id')
            ->whereNull('invoices.deleted_at')
            ->whereBetween('invoices.invoice_date', [$from, $to])
            ->whereIn('invoices.status', ['issued', 'partially_paid', 'paid'])
            ->groupBy('invoice_tax_summary.hsn_sac', 'invoice_tax_summary.tax_rate')
            ->orderBy('invoice_tax_summary.hsn_sac')
            ->get();

        if ($request->query('export') === 'csv') {
            $this->ensureExport();

            return $this->csv('gst-summary.csv', [
                'HSN/SAC', 'Rate %', 'Taxable Value', 'CGST', 'SGST', 'IGST', 'Total Tax',
            ], $rows->map(fn ($row) => [
                $row->hsn_sac,
                $row->tax_rate,
                $row->taxable_value,
                $row->cgst_amount,
                $row->sgst_amount,
                $row->igst_amount,
                $row->tax_amount,
            ]));
        }

        return view('reports.gst', compact('rows', 'from', 'to'));
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function range(Request $request): array
    {
        $from = $request->query('from') ?: Carbon::now()->startOfMonth()->toDateString();
        $to = $request->query('to') ?: Carbon::now()->endOfMonth()->toDateString();

        return [$from, $to];
    }

    private function ensureExport(): void
    {
        if (! auth()->user()->hasPermission('reports.export')) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * @param  array<int, string>  $header
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private function csv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $header);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}
