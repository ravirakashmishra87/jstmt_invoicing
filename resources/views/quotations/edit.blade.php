@extends('layouts.app')
@section('title', 'Edit quotation')
@section('page', 'Edit '.$quotation->quotation_number)
@section('content')
<div class="card"><div class="card-body">@include('quotations._form')</div></div>
@endsection
