@extends('layouts.app')
@section('title', 'New user')
@section('page', 'New user')
@section('content')
<div class="card"><div class="card-body">@include('users._form')</div></div>
@endsection
