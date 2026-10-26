@extends('layouts.app')
@section('title', 'Stock adjustment')
@section('page', 'Stock adjustment')
@section('content')
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('stock.adjustment.store') }}" class="row g-3">
        @csrf
        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="adjustment_date" class="form-control" required value="{{ old('adjustment_date', now()->toDateString()) }}"></div>
        <div class="col-md-4">
            <label class="form-label">Warehouse</label>
            <select name="warehouse_id" class="form-select" required>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-5"><label class="form-label">Reason</label><input name="reason" class="form-control" required value="{{ old('reason') }}"></div>
        <div class="col-md-5">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-select" required>
                <option value="">Select</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Direction</label>
            <select name="direction" class="form-select">
                <option value="in" @selected(old('direction') === 'in')>Stock in</option>
                <option value="out" @selected(old('direction', 'out') === 'out')>Stock out</option>
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">Quantity</label><input type="number" step="0.0001" name="quantity" class="form-control" value="{{ old('quantity', 1) }}" required></div>
        <div class="col-md-3">
            <label class="form-label">Serial out</label>
            <select name="product_serial_id" class="form-select">
                <option value="">—</option>
                @foreach($serials as $serial)
                    <option value="{{ $serial->id }}">{{ $serial->serial_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">New serial (stock in)</label><input name="serial_number" class="form-control" value="{{ old('serial_number') }}"></div>
        <div class="col-12"><button class="btn btn-primary">Post adjustment</button></div>
    </form>
</div></div>
@endsection
