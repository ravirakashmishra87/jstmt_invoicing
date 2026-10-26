@extends('layouts.app')
@section('title', 'Edit product')
@section('page', 'Edit '.$product->name)
@section('content')
<div class="card"><div class="card-body">
    @include('products._form')
    @if(auth()->user()->hasPermission('products.delete'))
        <form class="mt-3" method="POST" action="{{ route('products.destroy', $product) }}" onsubmit="return confirm('Archive this product?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Archive</button>
        </form>
    @endif
</div></div>
@endsection
