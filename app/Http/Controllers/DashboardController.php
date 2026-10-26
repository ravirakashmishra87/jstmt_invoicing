<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Quotation;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $salesStatuses = ['issued', 'partially_paid', 'paid'];

        $monthSales = (float) Invoice::query()
            ->whereIn('status', $salesStatuses)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->sum('grand_total');

        $monthCount = Invoice::query()
            ->whereIn('status', $salesStatuses)
            ->whereBetween('invoice_date', [$monthStart, $monthEnd])
            ->count();

        $pendingQuotations = Quotation::query()
            ->whereIn('status', ['draft', 'sent', 'under_negotiation'])
            ->count();

        $outstanding = (float) Invoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->sum('balance_due');

        $outstandingCount = Invoice::query()
            ->whereIn('status', ['issued', 'partially_paid'])
            ->where('balance_due', '>', 0)
            ->count();

        $lowStock = Product::query()
            ->where('is_active', true)
            ->where('reorder_level', '>', 0)
            ->withSum('stockBalances as qty_on_hand', 'quantity_on_hand')
            ->orderBy('name')
            ->get()
            ->filter(fn (Product $product) => (float) ($product->qty_on_hand ?? 0) <= (float) $product->reorder_level)
            ->take(8)
            ->values();

        $recentInvoices = Invoice::query()
            ->with('customer')
            ->latest('invoice_date')
            ->latest('id')
            ->limit(8)
            ->get();

        $topCustomers = Invoice::query()
            ->selectRaw('customer_id, SUM(grand_total) as sales_total, COUNT(*) as invoice_count')
            ->whereIn('status', $salesStatuses)
            ->groupBy('customer_id')
            ->orderByDesc('sales_total')
            ->with('customer')
            ->limit(5)
            ->get();

        return view('dashboard.index', compact(
            'monthSales',
            'monthCount',
            'pendingQuotations',
            'outstanding',
            'outstandingCount',
            'lowStock',
            'recentInvoices',
            'topCustomers',
        ));
    }
}
