@extends('layouts.app')
@section('title', 'New supplier')
@section('page', 'New supplier')
@section('content')
<div class="card"><div class="card-body">@include('suppliers._form')</div></div>
@endsection
