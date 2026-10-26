@extends('layouts.app')
@section('title', $product->name)
@section('page', $product->name)
@section('subtitle', $product->sku.($product->model_number ? ' · '.$product->model_number : ''))
@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    @include('partials.back-to-list', ['url' => route('products.index'), 'label' => 'Back to products'])
    @if(auth()->user()->hasPermission('products.edit'))
        <a class="btn btn-primary" href="{{ route('products.edit', $product) }}">Edit</a>
    @endif
    @if(auth()->user()->hasPermission('products.create'))
        <form method="POST" action="{{ route('products.clone', $product) }}">@csrf<button class="btn btn-outline-primary">Clone</button></form>
    @endif
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card"><div class="card-body">
            @if($product->primaryImageUrl())
                <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}" class="img-fluid rounded border mb-3" style="max-height: 260px; width: 100%; object-fit: contain; background: #f8fafc;">
            @else
                <div class="rounded border mb-3 d-flex align-items-center justify-content-center text-muted" style="height: 180px; background: #f8fafc;">No product image</div>
            @endif
            <div>List price <strong>{{ \App\Helpers\IndianFormat::money($product->list_price) }}</strong></div>
            <div>Purchase <strong>{{ \App\Helpers\IndianFormat::money($product->purchase_price) }}</strong></div>
            <div class="text-muted">HSN {{ $product->hsnSac->code ?? '—' }} · GST {{ \App\Helpers\IndianFormat::number($product->taxRate->rate ?? 0, 0) }}% · {{ $product->uom->code ?? '' }}</div>
            <div class="mt-2">{{ $product->description }}</div>
            <div class="mt-2">Serial tracking: {{ $product->track_serial ? 'Yes' : 'No' }}</div>
            @foreach($product->stockBalances as $balance)
                <div class="small text-muted">{{ $balance->warehouse->name ?? 'Warehouse' }}: {{ \App\Helpers\IndianFormat::number($balance->quantity_on_hand, 0) }} on hand</div>
            @endforeach
            @if(auth()->user()->hasPermission('products.edit') && $product->primaryImage())
                <form method="POST" action="{{ route('products.image.destroy', $product) }}" class="mt-3" onsubmit="return confirm('Remove this product image?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Remove image</button>
                </form>
            @endif
        </div></div>
    </div>
    <div class="col-lg-8">
        @foreach($product->specGroups as $group)
            <div class="card mb-2"><div class="card-body">
                <div class="fw-semibold">{{ $group->name }}</div>
                <table class="table table-sm mb-0">
                    @foreach($group->items as $item)
                        <tr><th class="w-25">{{ $item->label }}</th><td>{{ $item->value }}</td></tr>
                    @endforeach
                </table>
            </div></div>
        @endforeach
        @if($product->components->isNotEmpty())
            <div class="card"><div class="card-body">
                <div class="fw-semibold mb-2">Components</div>
                <ul class="mb-0">
                    @foreach($product->components as $component)
                        <li>{{ $component->component_name }} @if($component->brand_name)<span class="text-muted">— {{ $component->brand_name }}</span>@endif</li>
                    @endforeach
                </ul>
            </div></div>
        @endif
    </div>
</div>

@if($product->track_serial)
    <div class="card mt-3">
        <div class="card-header bg-white fw-semibold">Serials</div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Serial</th><th>Status</th><th>Warehouse</th><th class="text-end">Cost</th></tr></thead>
                <tbody>
                    @forelse($product->serials as $serial)
                        <tr>
                            <td>{{ $serial->serial_number }}</td>
                            <td>@include('partials.status', ['status' => $serial->status])</td>
                            <td>{{ $serial->warehouse->code ?? '' }}</td>
                            <td class="text-end">{{ \App\Helpers\IndianFormat::money($serial->purchase_cost) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted p-3">No serials recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if(auth()->user()->hasPermission('products.edit'))
            <div class="card-body border-top">
                <form method="POST" action="{{ route('products.serials.store', $product) }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input name="serial_number" class="form-control" placeholder="Serial number" required value="{{ old('serial_number') }}"></div>
                    <div class="col-md-3"><input type="number" step="0.01" name="purchase_cost" class="form-control" placeholder="Cost" value="{{ old('purchase_cost', $product->purchase_price) }}"></div>
                    <div class="col-md-3">
                        <select name="warehouse_id" class="form-select">
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-primary w-100">Add to stock</button></div>
                </form>
            </div>
        @endif
    </div>
@endif
@endsection
