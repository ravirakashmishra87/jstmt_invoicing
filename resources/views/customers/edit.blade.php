@extends('layouts.app')
@section('title', 'Edit customer')
@section('page', 'Customers')
@section('subtitle', $customer->customer_code)

@push('styles')
<style>
    .form-hero {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background:
            radial-gradient(1000px 240px at 100% -30%, rgba(142, 197, 239, .26), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16365c 50%, #1e4e79 100%);
        color: #e8f1f8;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.1rem;
        box-shadow: 0 18px 40px rgba(15, 39, 68, .16);
    }
    .form-hero::after {
        content: "";
        position: absolute;
        right: -36px; bottom: -70px;
        width: 160px; height: 160px;
        border: 16px solid rgba(255,255,255,.05);
        border-radius: 50%;
        pointer-events: none;
    }
    .form-hero .kicker {
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #93b4d0;
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .form-hero h1 {
        font-family: Barlow, sans-serif;
        font-size: clamp(1.35rem, 2vw, 1.75rem);
        font-weight: 700;
        color: #fff;
        margin: 0;
    }
    .form-hero p {
        margin: .4rem 0 0;
        color: #b7cee3;
        max-width: 36rem;
    }
    .form-hero .btn-ghost {
        background: transparent;
        border: 1px solid rgba(255,255,255,.28);
        color: #fff;
        border-radius: 10px;
        font-weight: 600;
    }
    .form-hero .btn-ghost:hover {
        background: rgba(255,255,255,.1);
        color: #fff;
    }
    .form-hero .btn-light {
        background: #fff;
        border-color: #fff;
        color: #0f2744;
        border-radius: 10px;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="form-hero">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
        <div>
            <div class="kicker">Customer master</div>
            <h1>Edit {{ $customer->legal_name }}</h1>
            <p>Update company identity, GST, contacts, credit settings, and addresses for this account.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('partials.back-to-list', ['url' => route('customers.index'), 'label' => 'Back to customers', 'class' => 'btn btn-ghost'])
            <a class="btn btn-light" href="{{ route('customers.show', $customer) }}"><i class="bi bi-eye"></i> View profile</a>
        </div>
    </div>
</div>

@include('customers._form')
@endsection
