@extends('layouts.app')
@section('title', 'Products')
@section('page', 'Products')
@section('subtitle', 'Product catalogue')

@section('content')
<div class="list-hero">
    <div class="kicker">Master data</div>
    <h1>Products</h1>
    <p>Catalogue of machines, spares, and services with pricing, HSN, and serial tracking.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="SKU, name, model">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
    </form>
    @if(auth()->user()->hasPermission('products.create'))
        <a class="btn btn-primary" href="{{ route('products.create') }}"><i class="bi bi-plus-lg"></i> New product</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Product list</h2>
        <span class="text-muted small">{{ $products->total() }} record{{ $products->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th></th>
                    <th>SKU</th>
                    <th>Name</th>
                    <th>HSN</th>
                    <th class="text-end">List price</th>
                    <th class="text-end">Stock</th>
                    <th>Serial</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td style="width:56px;">
                            @if($product->primaryImageUrl())
                                <img src="{{ $product->primaryImageUrl() }}" alt="" class="thumb-sm">
                            @else
                                <div class="thumb-placeholder">N/A</div>
                            @endif
                        </td>
                        <td class="text-muted">{{ $product->sku }}</td>
                        <td>
                            <div class="title">{{ $product->name }}</div>
                            @if($product->model_number)<div class="sub">{{ $product->model_number }}</div>@endif
                        </td>
                        <td>{{ $product->hsnSac->code ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($product->list_price) }}</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::number($product->qty_on_hand ?? 0, 0) }}</td>
                        <td>{{ $product->track_serial ? 'Yes' : 'No' }}</td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('products.view') ? route('products.show', $product) : null,
                                'editUrl' => auth()->user()->hasPermission('products.edit') ? route('products.edit', $product) : null,
                                'deleteUrl' => auth()->user()->hasPermission('products.delete') ? route('products.destroy', $product) : null,
                                'deleteConfirm' => 'Archive this product?',
                                'extra' => auth()->user()->hasPermission('products.create')
                                    ? '<form method="POST" action="'.route('products.clone', $product).'">'.csrf_field().'<button class="icon-btn" type="submit" title="Clone" aria-label="Clone"><i class="bi bi-copy"></i></button></form>'
                                    : null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="list-empty"><i class="bi bi-gear-wide-connected"></i>No products yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($products->hasPages())
    <div class="mt-3">{{ $products->links() }}</div>
@endif
@endsection
