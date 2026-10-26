@extends('layouts.app')
@section('title', 'Opening stock')
@section('page', 'Opening stock')
@section('content')
<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('stock.opening.store') }}" class="row g-3">
        @csrf
        <div class="col-md-6">
            <label class="form-label">Product</label>
            <select name="product_id" class="form-select" required>
                <option value="">Select</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-serial="{{ $product->track_serial ? '1' : '0' }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->sku }} — {{ $product->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Warehouse</label>
            <select name="warehouse_id" class="form-select">
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Unit cost</label>
            <input type="number" step="0.01" name="unit_cost" class="form-control" value="{{ old('unit_cost') }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Quantity</label>
            <input type="number" step="0.0001" name="quantity" class="form-control" value="{{ old('quantity', 1) }}">
            <div class="form-text">Ignored when the product tracks serials.</div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Serial numbers</label>
            <textarea name="serial_numbers" class="form-control" rows="4" placeholder="One serial per line">{{ old('serial_numbers') }}</textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Remarks</label>
            <input name="remarks" class="form-control" value="{{ old('remarks', 'Opening stock') }}">
        </div>
        <div class="col-12"><button class="btn btn-primary">Post opening stock</button></div>
    </form>
</div></div>
@endsection
