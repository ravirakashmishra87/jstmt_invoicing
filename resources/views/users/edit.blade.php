@extends('layouts.app')
@section('title', 'Edit user')
@section('page', 'Edit '.$user->name)
@section('content')
<div class="card"><div class="card-body">@include('users._form')</div></div>
@endsection
