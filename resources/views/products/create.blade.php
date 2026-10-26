@extends('layouts.app')
@section('title', 'New product')
@section('page', 'New product')
@section('content')
<div class="card"><div class="card-body">@include('products._form')</div></div>
@endsection
