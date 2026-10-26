@extends('layouts.app')
@section('title', 'New quotation')
@section('page', 'New quotation')
@section('content')
<div class="card"><div class="card-body">@include('quotations._form')</div></div>
@endsection
