@extends('layouts.app')
@section('title', 'Stock summary')
@section('page', 'Stock summary')
@section('content')
@include('reports._filters')
<div class="card"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>SKU</th><th>Product</th><th class="text-end">On hand</th><th class="text-end">Available</th><th class="text-end">Value</th><th class="text-end">Reorder</th></tr></thead>
        <tbody>
            @foreach($products as $product)
                @php
                    $onHand = (float) $product->stockBalances->sum('quantity_on_hand');
                    $reserved = (float) $product->stockBalances->sum('quantity_reserved');
                    $avg = (float) ($product->stockBalances->sortByDesc('quantity_on_hand')->first()->average_cost ?? 0);
                @endphp
                <tr>
                    <td>{{ $product->sku }}</td>
                    <td>{{ $product->name }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($onHand, 2) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($onHand - $reserved, 2) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($onHand * $avg) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($product->reorder_level, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div>
@endsection
