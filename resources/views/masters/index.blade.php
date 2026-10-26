@extends('layouts.app')
@section('title', 'Masters')
@section('page', 'Masters')
@section('subtitle', 'Reference data for documents and catalogue')

@push('styles')
<style>
    .masters-hero {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background:
            radial-gradient(1000px 240px at 100% -30%, rgba(142, 197, 239, .26), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16365c 50%, #1e4e79 100%);
        color: #e8f1f8;
        padding: 1.35rem 1.5rem;
        margin-bottom: 1.15rem;
        box-shadow: 0 18px 40px rgba(15, 39, 68, .16);
    }
    .masters-hero::after {
        content: "";
        position: absolute;
        right: -36px; bottom: -70px;
        width: 160px; height: 160px;
        border: 16px solid rgba(255,255,255,.05);
        border-radius: 50%;
        pointer-events: none;
    }
    .masters-hero .kicker {
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #93b4d0;
        font-weight: 600;
        margin-bottom: .25rem;
    }
    .masters-hero h1 {
        font-family: Barlow, sans-serif;
        font-size: clamp(1.35rem, 2vw, 1.75rem);
        font-weight: 700;
        color: #fff;
        margin: 0;
    }
    .masters-hero p {
        margin: .4rem 0 0;
        color: #b7cee3;
        max-width: 40rem;
    }
    .master-card {
        display: block;
        height: 100%;
        text-decoration: none;
        color: inherit;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1.15rem 1.2rem;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
        position: relative;
        overflow: hidden;
    }
    .master-card::before {
        content: "";
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        background: var(--steel);
        opacity: .85;
    }
    .master-card:hover {
        transform: translateY(-2px);
        border-color: #b7c9db;
        box-shadow: 0 14px 28px rgba(15, 39, 68, .1);
        color: inherit;
    }
    .master-card .icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: grid;
        place-items: center;
        background: linear-gradient(180deg, #eef5fb 0%, #e2eef8 100%);
        color: var(--steel);
        font-size: 1.15rem;
        margin-bottom: .85rem;
    }
    .master-card h2 {
        font-family: Barlow, sans-serif;
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--ink);
        margin: 0 0 .35rem;
    }
    .master-card .hint {
        color: #64748b;
        font-size: .9rem;
        margin: 0;
        line-height: 1.4;
    }
    .master-card .go {
        margin-top: .9rem;
        font-size: .82rem;
        font-weight: 700;
        color: var(--steel);
        display: inline-flex;
        align-items: center;
        gap: .3rem;
    }
</style>
@endpush

@section('content')
<div class="masters-hero">
    <div class="kicker">Administration</div>
    <h1>Master data</h1>
    <p>Maintain units, tax rates, HSN/SAC codes, payment terms, brands, and other reference lists used across quotations, invoices, and stock.</p>
</div>

<div class="row g-3">
    @foreach($types as $key => $meta)
        <div class="col-sm-6 col-xl-4">
            <a class="master-card" href="{{ route('masters.list', $key) }}">
                <div class="icon"><i class="bi bi-{{ $meta['icon'] ?? 'collection' }}"></i></div>
                <h2>{{ $meta['title'] }}</h2>
                <p class="hint">{{ $meta['hint'] }}</p>
                <span class="go">Open list <i class="bi bi-arrow-right"></i></span>
            </a>
        </div>
    @endforeach
</div>
@endsection
