@extends('layouts.app')
@section('title', 'Edit supplier')
@section('page', 'Edit '.$supplier->legal_name)
@section('content')
<div class="card"><div class="card-body">
    @include('suppliers._form')
    @if(auth()->user()->hasPermission('suppliers.delete'))
        <form class="mt-3" method="POST" action="{{ route('suppliers.destroy', $supplier) }}" onsubmit="return confirm('Archive this supplier?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger" type="submit">Archive</button>
        </form>
    @endif
</div></div>
@endsection
