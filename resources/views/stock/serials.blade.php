@extends('layouts.app')
@section('title', 'Serials')
@section('page', 'Serial numbers')
@section('subtitle', 'Serialized inventory')

@section('content')
<div class="list-hero">
    <div class="kicker">Inventory</div>
    <h1>Serial numbers</h1>
    <p>Track machine and spare serials from receipt through sale, return, repair, or scrap.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Serial or product">
        <select name="status" class="form-select" style="min-width:10rem;">
            <option value="">All statuses</option>
            @foreach(['in_stock', 'reserved', 'sold', 'returned', 'under_repair', 'scrapped'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('stock.index') }}"><i class="bi bi-box-seam"></i> Balances</a>
        <a class="btn btn-outline-secondary" href="{{ route('stock.ledger') }}"><i class="bi bi-journal-text"></i> Ledger</a>
    </div>
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Serial list</h2>
        <span class="text-muted small">{{ $serials->total() }} record{{ $serials->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Serial</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Warehouse</th>
                    <th>Invoice</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($serials as $serial)
                    <tr>
                        <td>
                            <div class="title">{{ $serial->serial_number }}</div>
                        </td>
                        <td>
                            <div class="title">{{ $serial->product->name ?? '—' }}</div>
                            @if($serial->product?->sku)<div class="sub">{{ $serial->product->sku }}</div>@endif
                        </td>
                        <td>
                            @php
                                $on = in_array($serial->status, ['in_stock', 'reserved'], true);
                            @endphp
                            <span class="status-pill {{ $on ? 'on' : 'off' }}">
                                <i class="bi bi-{{ $on ? 'check-circle' : 'slash-circle' }}"></i>
                                {{ \Illuminate\Support\Str::headline($serial->status) }}
                            </span>
                        </td>
                        <td>{{ $serial->warehouse->name ?? ($serial->warehouse->code ?? '—') }}</td>
                        <td>
                            @if($serial->invoice)
                                <a href="{{ route('invoices.show', $serial->invoice) }}">{{ $serial->invoice->invoice_number }}</a>
                            @else
                                —
                            @endif
                        </td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => $serial->product && auth()->user()->hasPermission('products.view')
                                    ? route('products.show', $serial->product)
                                    : ($serial->product ? route('stock.ledger', ['product_id' => $serial->product_id]) : null),
                                'editUrl' => auth()->user()->hasPermission('stock.edit') ? route('stock.adjustment') : null,
                                'extra' => $serial->invoice && auth()->user()->hasPermission('invoices.view')
                                    ? '<a class="icon-btn" href="'.route('invoices.show', $serial->invoice).'" title="Invoice" aria-label="Invoice"><i class="bi bi-receipt"></i></a>'
                                    : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="list-empty"><i class="bi bi-upc-scan"></i>No serials found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($serials->hasPages())
    <div class="mt-3">{{ $serials->links() }}</div>
@endif
@endsection
