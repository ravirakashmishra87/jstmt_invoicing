@extends('layouts.app')
@section('title', $supplier->legal_name)
@section('page', 'Supplier')
@section('subtitle', $supplier->supplier_code)

@section('content')
<div class="list-hero">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
        <div>
            <div class="kicker">Supplier profile</div>
            <h1>{{ $supplier->legal_name }}</h1>
            <p>{{ $supplier->trade_name ?: 'Vendor account details' }}</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @include('partials.back-to-list', ['url' => route('suppliers.index'), 'label' => 'Back to suppliers', 'class' => 'btn btn-outline-secondary'])
            @if(auth()->user()->hasPermission('suppliers.edit'))
                <a class="btn btn-primary" href="{{ route('suppliers.edit', $supplier) }}"><i class="bi bi-pencil-square"></i> Edit</a>
            @endif
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="list-panel">
            <div class="list-panel-head"><h2>Company &amp; tax</h2></div>
            <div class="p-3">
                <div class="mb-3"><div class="text-muted small text-uppercase fw-bold">GSTIN</div><div class="fw-semibold">{{ $supplier->gstin ?: 'Unregistered' }}</div></div>
                <div class="mb-3"><div class="text-muted small text-uppercase fw-bold">PAN</div><div class="fw-semibold">{{ $supplier->pan ?: '—' }}</div></div>
                <div class="mb-3"><div class="text-muted small text-uppercase fw-bold">GST type</div><div class="fw-semibold">{{ \Illuminate\Support\Str::headline($supplier->gst_registration_type) }}</div></div>
                <div><div class="text-muted small text-uppercase fw-bold">Status</div>
                    <span class="status-pill {{ $supplier->is_active ? 'on' : 'off' }}">{{ $supplier->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="list-panel">
            <div class="list-panel-head"><h2>Contact</h2></div>
            <div class="p-3">
                <div class="mb-3"><div class="text-muted small text-uppercase fw-bold">Person</div><div class="fw-semibold">{{ $supplier->contact_person ?: '—' }}</div></div>
                <div class="mb-3"><div class="text-muted small text-uppercase fw-bold">Mobile</div><div class="fw-semibold">{{ $supplier->mobile ?: '—' }}</div></div>
                <div class="mb-3"><div class="text-muted small text-uppercase fw-bold">Email</div><div class="fw-semibold">{{ $supplier->email ?: '—' }}</div></div>
                <div><div class="text-muted small text-uppercase fw-bold">Bank</div><div class="fw-semibold">{{ collect([$supplier->bank_name, $supplier->bank_account, $supplier->bank_ifsc])->filter()->implode(' · ') ?: '—' }}</div></div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="list-panel">
            <div class="list-panel-head"><h2>Addresses</h2></div>
            <div class="p-3">
                @forelse($supplier->addresses as $address)
                    <div class="mb-2">
                        <div class="fw-bold text-uppercase small" style="color:var(--steel);">{{ \Illuminate\Support\Str::headline($address->type) }}</div>
                        <div>{{ $address->address_line1 }} {{ $address->address_line2 }}</div>
                        <div class="text-muted">{{ collect([$address->city, $address->district, $address->state->name ?? null, $address->pin])->filter()->implode(', ') }}</div>
                    </div>
                @empty
                    <div class="text-muted">No addresses on file.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
