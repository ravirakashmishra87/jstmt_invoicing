@extends('layouts.app')
@section('title', 'Dashboard · JST')
@section('page', 'Dashboard')
@section('subtitle', now()->format('l, d M Y'))

@php
    $hour = (int) now()->timezone(config('app.timezone'))->format('G');
    $greeting = match (true) {
        $hour < 12 => 'Good morning',
        $hour < 17 => 'Good afternoon',
        default => 'Good evening',
    };
    $firstName = collect(preg_split('/\s+/', trim(auth()->user()->name ?? 'there')))->first() ?: 'there';
@endphp

@push('styles')
<style>
    .dash-hero {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background:
            radial-gradient(900px 260px at 100% -20%, rgba(142, 197, 239, .28), transparent 55%),
            radial-gradient(520px 200px at 0% 120%, rgba(47, 111, 163, .35), transparent 50%),
            linear-gradient(135deg, #0f2744 0%, #16365c 48%, #1e4e79 100%);
        color: #e8f1f8;
        padding: 1.45rem 1.5rem 1.35rem;
        margin-bottom: 1.15rem;
        box-shadow: 0 18px 40px rgba(15, 39, 68, .16);
    }
    .dash-hero::after {
        content: "";
        position: absolute;
        right: -48px;
        bottom: -72px;
        width: 200px;
        height: 200px;
        border: 20px solid rgba(255,255,255,.05);
        border-radius: 50%;
        pointer-events: none;
    }
    .dash-hero .kicker {
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #93b4d0;
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .dash-hero h1 {
        font-family: Barlow, sans-serif;
        font-size: clamp(1.35rem, 2.2vw, 1.85rem);
        font-weight: 700;
        color: #fff;
        margin: 0;
        line-height: 1.2;
    }
    .dash-hero p {
        margin: .4rem 0 0;
        color: #b7cee3;
        max-width: 36rem;
    }
    .dash-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        margin-top: 1rem;
        position: relative;
        z-index: 1;
    }
    .dash-hero-actions .btn {
        border-radius: 10px;
        font-weight: 600;
    }
    .dash-hero-actions .btn-light {
        background: #fff;
        color: var(--ink);
        border: none;
    }
    .dash-hero-actions .btn-ghost {
        background: rgba(255,255,255,.08);
        border: 1px solid rgba(255,255,255,.18);
        color: #e8f1f8;
    }
    .dash-hero-actions .btn-ghost:hover {
        background: rgba(255,255,255,.14);
        color: #fff;
    }
    .dash-metric {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1.05rem 1.1rem;
        height: 100%;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        position: relative;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease;
    }
    .dash-metric:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 28px rgba(15, 39, 68, .08);
    }
    .dash-metric::before {
        content: "";
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        background: var(--accent, var(--steel));
    }
    .dash-metric-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: .75rem;
        margin-bottom: .65rem;
    }
    .dash-metric-label {
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
    }
    .dash-metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 11px;
        display: grid;
        place-items: center;
        font-size: 1.1rem;
        flex-shrink: 0;
        background: var(--icon-bg, #eef5fb);
        color: var(--accent, var(--steel));
    }
    .dash-metric-value {
        font-family: Barlow, sans-serif;
        font-size: 1.45rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.15;
        letter-spacing: .01em;
    }
    .dash-metric-meta {
        margin-top: .35rem;
        font-size: .86rem;
        color: #64748b;
    }
    .dash-metric a.stretched {
        position: absolute;
        inset: 0;
        z-index: 1;
    }
    .dash-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .dash-panel-head {
        padding: .95rem 1.15rem;
        border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .dash-panel-head h2 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .dash-panel-head h2 i { color: var(--steel); }
    .dash-panel-head a {
        font-size: .82rem;
        font-weight: 600;
        text-decoration: none;
        color: var(--steel);
    }
    .dash-panel-head a:hover { color: var(--ink); }
    .dash-panel-body { flex: 1; }
    .dash-table { margin: 0; }
    .dash-table thead th {
        font-size: .7rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        background: #f8fafc;
        border-bottom-color: var(--line);
        padding: .7rem 1.05rem;
        white-space: nowrap;
        font-weight: 700;
    }
    .dash-table tbody td {
        padding: .8rem 1.05rem;
        vertical-align: middle;
        border-color: #edf2f7;
    }
    .dash-table tbody tr:hover { background: #f8fafc; }
    .dash-table .title { font-weight: 700; color: var(--ink); }
    .dash-table .title a { color: inherit; text-decoration: none; }
    .dash-table .title a:hover { color: var(--steel); }
    .dash-table .sub { color: #64748b; font-size: .84rem; margin-top: .1rem; }
    .dash-empty {
        text-align: center;
        padding: 2rem 1rem;
        color: #64748b;
    }
    .dash-empty i {
        font-size: 1.4rem;
        color: #94a3b8;
        display: block;
        margin-bottom: .4rem;
    }
    .dash-rank {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        display: inline-grid;
        place-items: center;
        font-size: .75rem;
        font-weight: 700;
        background: #eef5fb;
        color: var(--steel);
        flex-shrink: 0;
    }
    .dash-rank.top { background: #0f2744; color: #fff; }
    .dash-customer {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #edf2f7;
    }
    .dash-customer:last-child { border-bottom: 0; }
    .dash-customer:hover { background: #f8fafc; }
    .dash-stock {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
        padding: .85rem 1.15rem;
        border-bottom: 1px solid #edf2f7;
    }
    .dash-stock:last-child { border-bottom: 0; }
    .dash-stock:hover { background: #f8fafc; }
    .dash-stock-bar {
        height: 4px;
        border-radius: 999px;
        background: #e2e8f0;
        margin-top: .4rem;
        overflow: hidden;
        max-width: 140px;
    }
    .dash-stock-bar > span {
        display: block;
        height: 100%;
        border-radius: 999px;
        background: #b91c1c;
    }
    .dash-badge-warn {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        padding: .18rem .5rem;
        border-radius: 999px;
        font-size: .72rem;
        font-weight: 700;
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
        white-space: nowrap;
    }
</style>
@endpush

@section('content')
<div class="dash-hero">
    <div class="kicker">Operations overview · {{ now()->format('F Y') }}</div>
    <h1>{{ $greeting }}, {{ $firstName }}</h1>
    <p>Track sales, quotations, receivables, and stock health for JST Machine Tools.</p>
    <div class="dash-hero-actions">
        @if(auth()->user()->hasPermission('quotations.create'))
            <a class="btn btn-light btn-sm" href="{{ route('quotations.create') }}"><i class="bi bi-file-earmark-plus"></i> New quotation</a>
        @endif
        @if(auth()->user()->hasPermission('invoices.create'))
            <a class="btn btn-ghost btn-sm" href="{{ route('invoices.create') }}"><i class="bi bi-receipt"></i> New invoice</a>
        @endif
        @if(auth()->user()->hasPermission('customers.create'))
            <a class="btn btn-ghost btn-sm" href="{{ route('customers.create') }}"><i class="bi bi-person-plus"></i> New customer</a>
        @endif
        @if(auth()->user()->hasPermission('stock.view'))
            <a class="btn btn-ghost btn-sm" href="{{ route('stock.index') }}"><i class="bi bi-box-seam"></i> Stock</a>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6 col-xl-3">
        <div class="dash-metric" style="--accent:#1e4e79; --icon-bg:#e8f0f8;">
            <div class="dash-metric-top">
                <div class="dash-metric-label">Sales this month</div>
                <div class="dash-metric-icon"><i class="bi bi-currency-rupee"></i></div>
            </div>
            <div class="dash-metric-value">{{ \App\Helpers\IndianFormat::money($monthSales) }}</div>
            <div class="dash-metric-meta">{{ $monthCount }} issued invoice{{ $monthCount === 1 ? '' : 's' }}</div>
            @if(auth()->user()->hasPermission('invoices.view'))
                <a class="stretched" href="{{ route('invoices.index') }}" aria-label="View invoices"></a>
            @endif
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="dash-metric" style="--accent:#0f766e; --icon-bg:#ecfdf8;">
            <div class="dash-metric-top">
                <div class="dash-metric-label">Pending quotations</div>
                <div class="dash-metric-icon"><i class="bi bi-file-earmark-text"></i></div>
            </div>
            <div class="dash-metric-value">{{ \App\Helpers\IndianFormat::number($pendingQuotations, 0) }}</div>
            <div class="dash-metric-meta">Draft, sent, or under negotiation</div>
            @if(auth()->user()->hasPermission('quotations.view'))
                <a class="stretched" href="{{ route('quotations.index') }}" aria-label="View quotations"></a>
            @endif
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="dash-metric" style="--accent:#b45309; --icon-bg:#fff7ed;">
            <div class="dash-metric-top">
                <div class="dash-metric-label">Outstanding</div>
                <div class="dash-metric-icon"><i class="bi bi-wallet2"></i></div>
            </div>
            <div class="dash-metric-value">{{ \App\Helpers\IndianFormat::money($outstanding) }}</div>
            <div class="dash-metric-meta">{{ $outstandingCount }} open invoice{{ $outstandingCount === 1 ? '' : 's' }}</div>
            @if(auth()->user()->hasPermission('receipts.view'))
                <a class="stretched" href="{{ route('receipts.index') }}" aria-label="View receipts"></a>
            @elseif(auth()->user()->hasPermission('invoices.view'))
                <a class="stretched" href="{{ route('invoices.index') }}" aria-label="View invoices"></a>
            @endif
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="dash-metric" style="--accent:#b91c1c; --icon-bg:#fef2f2;">
            <div class="dash-metric-top">
                <div class="dash-metric-label">Low stock</div>
                <div class="dash-metric-icon"><i class="bi bi-exclamation-triangle"></i></div>
            </div>
            <div class="dash-metric-value">{{ $lowStock->count() }}</div>
            <div class="dash-metric-meta">At or below reorder level</div>
            @if(auth()->user()->hasPermission('stock.view'))
                <a class="stretched" href="{{ route('stock.index') }}" aria-label="View stock"></a>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2><i class="bi bi-receipt"></i> Recent invoices</h2>
                @if(auth()->user()->hasPermission('invoices.view'))
                    <a href="{{ route('invoices.index') }}">View all</a>
                @endif
            </div>
            <div class="dash-panel-body table-responsive">
                <table class="table dash-table align-middle">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentInvoices as $invoice)
                            <tr>
                                <td>
                                    <div class="title">
                                        @if(auth()->user()->hasPermission('invoices.view'))
                                            <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a>
                                        @else
                                            {{ $invoice->invoice_number }}
                                        @endif
                                    </div>
                                    <div class="sub">{{ \App\Helpers\IndianFormat::date($invoice->invoice_date) }}</div>
                                </td>
                                <td>
                                    <div class="title" style="font-weight:600;">{{ $invoice->buyer_name ?: ($invoice->customer->legal_name ?? '—') }}</div>
                                </td>
                                <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($invoice->grand_total) }}</td>
                                <td>@include('partials.status', ['status' => $invoice->status])</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="dash-empty"><i class="bi bi-receipt"></i>No invoices yet.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="dash-panel mb-3">
            <div class="dash-panel-head">
                <h2><i class="bi bi-trophy"></i> Top customers</h2>
                @if(auth()->user()->hasPermission('customers.view'))
                    <a href="{{ route('customers.index') }}">Customers</a>
                @endif
            </div>
            <div class="dash-panel-body">
                @forelse($topCustomers as $index => $row)
                    <div class="dash-customer">
                        <span class="dash-rank {{ $index < 3 ? 'top' : '' }}">{{ $index + 1 }}</span>
                        <div class="flex-grow-1 min-w-0">
                            <div class="title text-truncate" style="font-weight:700; color:var(--ink);">
                                {{ $row->customer->legal_name ?? 'Customer' }}
                            </div>
                            <div class="sub" style="color:#64748b; font-size:.84rem;">
                                {{ \App\Helpers\IndianFormat::number($row->invoice_count, 0) }} invoice{{ (int) $row->invoice_count === 1 ? '' : 's' }}
                            </div>
                        </div>
                        <div class="fw-semibold text-nowrap" style="color:var(--ink);">
                            {{ \App\Helpers\IndianFormat::money($row->sales_total) }}
                        </div>
                    </div>
                @empty
                    <div class="dash-empty"><i class="bi bi-people"></i>No billed sales yet.</div>
                @endforelse
            </div>
        </div>

        <div class="dash-panel">
            <div class="dash-panel-head">
                <h2><i class="bi bi-box-seam"></i> Low stock</h2>
                @if(auth()->user()->hasPermission('stock.view'))
                    <a href="{{ route('stock.index') }}">Stock</a>
                @endif
            </div>
            <div class="dash-panel-body">
                @forelse($lowStock as $product)
                    @php
                        $onHand = (float) ($product->qty_on_hand ?? 0);
                        $reorder = max((float) $product->reorder_level, 0.0001);
                        $ratio = min(100, max(0, ($onHand / $reorder) * 100));
                    @endphp
                    <div class="dash-stock">
                        <div class="min-w-0 flex-grow-1">
                            <div class="title text-truncate" style="font-weight:700; color:var(--ink);">
                                @if(auth()->user()->hasPermission('products.view'))
                                    <a href="{{ route('products.show', $product) }}" style="color:inherit; text-decoration:none;">{{ $product->name }}</a>
                                @else
                                    {{ $product->name }}
                                @endif
                            </div>
                            <div class="sub" style="color:#64748b; font-size:.84rem;">{{ $product->sku }}</div>
                            <div class="dash-stock-bar"><span style="width: {{ $ratio }}%;"></span></div>
                        </div>
                        <span class="dash-badge-warn">
                            {{ \App\Helpers\IndianFormat::number($onHand, 0) }} / {{ \App\Helpers\IndianFormat::number($product->reorder_level, 0) }}
                        </span>
                    </div>
                @empty
                    <div class="dash-empty"><i class="bi bi-check-circle"></i>All tracked items are above reorder.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
