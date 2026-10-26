@extends('layouts.app')
@section('title', 'Stock')
@section('page', 'Stock')
@section('subtitle', 'Inventory balances')

@section('content')
<div class="list-hero">
    <div class="kicker">Inventory</div>
    <h1>Stock balances</h1>
    <p>On-hand quantities, reservations, and stock value by warehouse for every product in the catalogue.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Search SKU or name">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
    </form>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('stock.ledger') }}"><i class="bi bi-journal-text"></i> Ledger</a>
        <a class="btn btn-outline-secondary" href="{{ route('stock.serials') }}"><i class="bi bi-upc-scan"></i> Serials</a>
        @if(auth()->user()->hasPermission('stock.edit'))
            <a class="btn btn-outline-primary" href="{{ route('stock.adjustment') }}"><i class="bi bi-sliders"></i> Adjustment</a>
        @endif
        @if(auth()->user()->hasPermission('stock.create'))
            <a class="btn btn-primary" href="{{ route('stock.opening') }}"><i class="bi bi-plus-lg"></i> Opening stock</a>
        @endif
    </div>
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Balance list</h2>
        <span class="text-muted small">{{ $products->total() }} product{{ $products->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th class="text-end">On hand</th>
                    <th class="text-end">Reserved</th>
                    <th class="text-end">Avg cost</th>
                    <th class="text-end">Value</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    @php
                        $viewUrl = auth()->user()->hasPermission('products.view')
                            ? route('products.show', $product)
                            : route('stock.ledger', ['product_id' => $product->id]);
                        $editUrl = auth()->user()->hasPermission('stock.edit')
                            ? route('stock.adjustment')
                            : (auth()->user()->hasPermission('products.edit') ? route('products.edit', $product) : null);
                        $balances = $product->stockBalances;
                    @endphp
                    @if($balances->isEmpty())
                        <tr>
                            <td class="text-muted">{{ $product->sku }}</td>
                            <td>
                                <div class="title">{{ $product->name }}</div>
                                @if($product->uom)<div class="sub">{{ $product->uom->code ?? $product->uom->name }}</div>@endif
                            </td>
                            <td class="text-muted">—</td>
                            <td class="text-end">0.00</td>
                            <td class="text-end">0.00</td>
                            <td class="text-end">—</td>
                            <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money(0) }}</td>
                            <td class="text-end">
                                @include('partials.row-actions', [
                                    'viewUrl' => $viewUrl,
                                    'editUrl' => $editUrl,
                                    'extra' => '<a class="icon-btn" href="'.route('stock.ledger', ['product_id' => $product->id]).'" title="Ledger" aria-label="Ledger"><i class="bi bi-journal-text"></i></a>',
                                ])
                            </td>
                        </tr>
                    @else
                        @foreach($balances as $balance)
                            <tr>
                                <td class="text-muted">{{ $product->sku }}</td>
                                <td>
                                    <div class="title">{{ $product->name }}</div>
                                    @if($product->uom)<div class="sub">{{ $product->uom->code ?? $product->uom->name }}</div>@endif
                                </td>
                                <td>{{ $balance->warehouse->name ?? '—' }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::number($balance->quantity_on_hand, 2) }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::number($balance->quantity_reserved, 2) }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::money($balance->average_cost) }}</td>
                                <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money((float) $balance->quantity_on_hand * (float) $balance->average_cost) }}</td>
                                <td class="text-end">
                                    @include('partials.row-actions', [
                                        'viewUrl' => $viewUrl,
                                        'editUrl' => $editUrl,
                                        'extra' => '<a class="icon-btn" href="'.route('stock.ledger', ['product_id' => $product->id]).'" title="Ledger" aria-label="Ledger"><i class="bi bi-journal-text"></i></a>',
                                    ])
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr><td colspan="8"><div class="list-empty"><i class="bi bi-box-seam"></i>No stock records yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($products->hasPages())
    <div class="mt-3">{{ $products->links() }}</div>
@endif
@endsection
