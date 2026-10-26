@extends('layouts.app')
@section('title', 'Stock ledger')
@section('page', 'Stock ledger')
@section('subtitle', 'Inventory movements')

@section('content')
<div class="list-hero">
    <div class="kicker">Inventory</div>
    <h1>Stock ledger</h1>
    <p>Chronological in/out movements with running balances for opening, invoices, and adjustments.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap flex-grow-1" method="GET" style="max-width:none; flex:1 1 28rem;">
        <select name="product_id" class="form-select" style="min-width:14rem;">
            <option value="">All products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}" @selected((string) $productId === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
            @endforeach
        </select>
        <input type="date" name="from" class="form-control" value="{{ $from }}" title="From">
        <input type="date" name="to" class="form-control" value="{{ $to }}" title="To">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('stock.index') }}"><i class="bi bi-box-seam"></i> Balances</a>
        <a class="btn btn-outline-secondary" href="{{ route('stock.serials') }}"><i class="bi bi-upc-scan"></i> Serials</a>
    </div>
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Movement list</h2>
        <span class="text-muted small">{{ $entries->total() }} entr{{ $entries->total() === 1 ? 'y' : 'ies' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Document</th>
                    <th>Serial</th>
                    <th class="text-end">In</th>
                    <th class="text-end">Out</th>
                    <th class="text-end">Balance</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($entries as $entry)
                    <tr>
                        <td class="text-muted">{{ \App\Helpers\IndianFormat::date($entry->transaction_date) }}</td>
                        <td>
                            <div class="title">{{ $entry->product->name ?? '—' }}</div>
                            @if($entry->product?->sku)<div class="sub">{{ $entry->product->sku }}</div>@endif
                        </td>
                        <td>
                            <div class="title">{{ \Illuminate\Support\Str::headline($entry->document_type) }}</div>
                            <div class="sub">{{ $entry->document_number ?: '—' }}</div>
                        </td>
                        <td>{{ $entry->serial_number ?: '—' }}</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::number($entry->qty_in, 2) }}</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::number($entry->qty_out, 2) }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::number($entry->balance_qty, 2) }}</td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => $entry->product && auth()->user()->hasPermission('products.view')
                                    ? route('products.show', $entry->product)
                                    : ($entry->product ? route('stock.ledger', ['product_id' => $entry->product_id]) : null),
                                'editUrl' => auth()->user()->hasPermission('stock.edit') ? route('stock.adjustment') : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="list-empty"><i class="bi bi-journal-text"></i>No movements found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($entries->hasPages())
    <div class="mt-3">{{ $entries->links() }}</div>
@endif
@endsection
