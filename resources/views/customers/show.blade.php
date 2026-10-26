@extends('layouts.app')
@section('title', $customer->legal_name)
@section('page', 'Customer')
@section('subtitle', $customer->customer_code)

@php
    $initials = collect(preg_split('/\s+/', trim($customer->legal_name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $billing = $customer->addresses->firstWhere('type', 'billing')
        ?? $customer->addresses->firstWhere('is_default', true)
        ?? $customer->addresses->first();
    $shipping = $customer->addresses->firstWhere('type', 'shipping');
@endphp

@push('styles')
<style>
    .cust-hero {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background:
            radial-gradient(1200px 280px at 100% -20%, rgba(142, 197, 239, .28), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16365c 48%, #1e4e79 100%);
        color: #e8f1f8;
        padding: 1.5rem 1.6rem 1.35rem;
        margin-bottom: 1.1rem;
        box-shadow: 0 18px 40px rgba(15, 39, 68, .18);
    }
    .cust-hero::after {
        content: "";
        position: absolute;
        inset: auto -40px -60px auto;
        width: 180px;
        height: 180px;
        border: 18px solid rgba(255,255,255,.05);
        border-radius: 50%;
        pointer-events: none;
    }
    .cust-avatar {
        width: 72px;
        height: 72px;
        border-radius: 18px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.18);
        display: grid;
        place-items: center;
        font-family: Barlow, sans-serif;
        font-size: 1.45rem;
        font-weight: 700;
        letter-spacing: .04em;
        flex-shrink: 0;
    }
    .cust-kicker {
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #93b4d0;
        font-weight: 600;
        margin-bottom: .2rem;
    }
    .cust-title {
        font-family: Barlow, sans-serif;
        font-size: clamp(1.35rem, 2vw, 1.85rem);
        font-weight: 700;
        margin: 0;
        color: #fff;
        line-height: 1.15;
    }
    .cust-trade {
        color: #b7cee3;
        margin-top: .25rem;
    }
    .cust-pill {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .28rem .65rem;
        border-radius: 999px;
        font-size: .78rem;
        font-weight: 600;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.14);
        color: #e8f1f8;
    }
    .cust-pill.active { background: rgba(34, 197, 94, .18); border-color: rgba(134, 239, 172, .35); color: #bbf7d0; }
    .cust-pill.inactive { background: rgba(248, 113, 113, .16); border-color: rgba(252, 165, 165, .35); color: #fecaca; }
    .cust-actions .btn {
        border-radius: 10px;
        font-weight: 600;
    }
    .cust-actions .btn-light {
        background: #fff;
        border-color: #fff;
        color: #0f2744;
    }
    .cust-actions .btn-ghost {
        background: transparent;
        border: 1px solid rgba(255,255,255,.28);
        color: #fff;
    }
    .cust-actions .btn-ghost:hover {
        background: rgba(255,255,255,.1);
        color: #fff;
    }
    .metric-strip {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .85rem;
        margin-bottom: 1.1rem;
    }
    @media (max-width: 991.98px) {
        .metric-strip { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 575.98px) {
        .metric-strip { grid-template-columns: 1fr; }
        .cust-hero { padding: 1.15rem; }
    }
    .metric-tile {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 1rem 1.05rem;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        position: relative;
        overflow: hidden;
    }
    .metric-tile::before {
        content: "";
        position: absolute;
        left: 0; top: 0; bottom: 0;
        width: 3px;
        background: var(--steel);
    }
    .metric-tile .label {
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: var(--slate);
        font-weight: 700;
        margin-bottom: .35rem;
    }
    .metric-tile .value {
        font-family: Barlow, sans-serif;
        font-size: 1.35rem;
        font-weight: 700;
        color: var(--ink);
        line-height: 1.1;
    }
    .metric-tile .hint { color: #64748b; font-size: .8rem; margin-top: .25rem; }
    .metric-tile.warn::before { background: #c2410c; }
    .metric-tile.warn .value { color: #9a3412; }
    .panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        height: 100%;
    }
    .panel-head {
        padding: .95rem 1.15rem;
        border-bottom: 1px solid var(--line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .panel-head h2 {
        margin: 0;
        font-size: .95rem;
        font-weight: 700;
        color: var(--ink);
    }
    .panel-body { padding: 1.15rem; }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem 1.25rem;
    }
    @media (max-width: 575.98px) {
        .info-grid { grid-template-columns: 1fr; }
    }
    .info-item .lbl {
        font-size: .72rem;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 700;
        margin-bottom: .2rem;
    }
    .info-item .val {
        color: #0f172a;
        font-weight: 600;
        word-break: break-word;
    }
    .info-item .sub { color: #64748b; font-size: .88rem; margin-top: .15rem; }
    .addr-card {
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 55%);
        height: 100%;
    }
    .addr-card .type {
        font-size: .72rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--steel);
        margin-bottom: .45rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .addr-card .lines {
        color: #334155;
        line-height: 1.45;
        font-weight: 500;
    }
    .chip {
        display: inline-flex;
        align-items: center;
        padding: .15rem .45rem;
        border-radius: 999px;
        font-size: .68rem;
        font-weight: 700;
        background: #e2e8f0;
        color: #334155;
        letter-spacing: .02em;
    }
    .invoice-table tbody tr:hover { background: #f8fafc; }
    .invoice-table a { font-weight: 700; color: var(--steel); text-decoration: none; }
    .invoice-table a:hover { text-decoration: underline; }
    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #64748b;
    }
    .empty-state i { font-size: 1.6rem; color: #94a3b8; display: block; margin-bottom: .5rem; }
</style>
@endpush

@section('content')
<div class="cust-hero">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
        <div class="d-flex gap-3 align-items-start">
            <div class="cust-avatar">{{ $initials ?: 'CU' }}</div>
            <div>
                <div class="cust-kicker">Customer profile</div>
                <h1 class="cust-title">{{ $customer->legal_name }}</h1>
                @if($customer->trade_name && $customer->trade_name !== $customer->legal_name)
                    <div class="cust-trade">Trading as {{ $customer->trade_name }}</div>
                @endif
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <span class="cust-pill"><i class="bi bi-hash"></i> {{ $customer->customer_code }}</span>
                    <span class="cust-pill {{ $customer->is_active ? 'active' : 'inactive' }}">
                        <i class="bi bi-{{ $customer->is_active ? 'check-circle' : 'slash-circle' }}"></i>
                        {{ $customer->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span class="cust-pill"><i class="bi bi-building"></i> {{ \Illuminate\Support\Str::headline($customer->gst_registration_type) }}</span>
                    @if($billing?->state)
                        <span class="cust-pill"><i class="bi bi-geo-alt"></i> {{ $billing->state->name }}</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="cust-actions d-flex flex-wrap gap-2">
            @include('partials.back-to-list', ['url' => route('customers.index'), 'label' => 'Back to customers', 'class' => 'btn btn-ghost'])
            @if(auth()->user()->hasPermission('customers.edit'))
                <a class="btn btn-light" href="{{ route('customers.edit', $customer) }}"><i class="bi bi-pencil-square"></i> Edit</a>
            @endif
            @if(auth()->user()->hasPermission('invoices.create'))
                <a class="btn btn-ghost" href="{{ route('invoices.create', ['customer_id' => $customer->id]) }}"><i class="bi bi-receipt"></i> New invoice</a>
            @endif
            @if(auth()->user()->hasPermission('quotations.create'))
                <a class="btn btn-ghost" href="{{ route('quotations.create', ['customer_id' => $customer->id]) }}"><i class="bi bi-file-earmark-text"></i> New quotation</a>
            @endif
            @if(auth()->user()->hasPermission('customers.delete'))
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Archive this customer?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-ghost" type="submit"><i class="bi bi-archive"></i> Archive</button>
                </form>
            @endif
        </div>
    </div>
</div>

<div class="metric-strip">
    <div class="metric-tile">
        <div class="label">Lifetime billed</div>
        <div class="value">{{ \App\Helpers\IndianFormat::money($summary['billed_total']) }}</div>
        <div class="hint">Issued invoices</div>
    </div>
    <div class="metric-tile {{ $summary['outstanding'] > 0 ? 'warn' : '' }}">
        <div class="label">Outstanding</div>
        <div class="value">{{ \App\Helpers\IndianFormat::money($summary['outstanding']) }}</div>
        <div class="hint">Balance receivable</div>
    </div>
    <div class="metric-tile">
        <div class="label">Collected</div>
        <div class="value">{{ \App\Helpers\IndianFormat::money($summary['paid_total']) }}</div>
        <div class="hint">Allocated receipts</div>
    </div>
    <div class="metric-tile">
        <div class="label">Invoices</div>
        <div class="value">{{ $summary['invoice_count'] }}</div>
        <div class="hint">Excluding cancelled</div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="panel">
            <div class="panel-head">
                <h2><i class="bi bi-person-vcard me-1"></i> Company &amp; tax</h2>
            </div>
            <div class="panel-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="lbl">GSTIN</div>
                        <div class="val">{{ $customer->gstin ?: 'Unregistered' }}</div>
                        <div class="sub">{{ \Illuminate\Support\Str::headline($customer->gst_registration_type) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">PAN</div>
                        <div class="val">{{ $customer->pan ?: '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Payment terms</div>
                        <div class="val">{{ $customer->paymentTerm->name ?? '—' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Credit limit</div>
                        <div class="val">{{ \App\Helpers\IndianFormat::money($customer->credit_limit) }}</div>
                        <div class="sub">Opening balance {{ \App\Helpers\IndianFormat::money($customer->opening_balance) }}</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Salesperson</div>
                        <div class="val">{{ $customer->salesperson->name ?? 'Unassigned' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="lbl">Since</div>
                        <div class="val">{{ \App\Helpers\IndianFormat::date($customer->created_at) }}</div>
                    </div>
                </div>
                @if($customer->notes)
                    <div class="mt-3 pt-3 border-top">
                        <div class="info-item">
                            <div class="lbl">Internal notes</div>
                            <div class="val" style="font-weight:500;white-space:pre-wrap;">{{ $customer->notes }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <div class="panel-head">
                <h2><i class="bi bi-telephone me-1"></i> Primary contact</h2>
            </div>
            <div class="panel-body">
                <div class="info-item mb-3">
                    <div class="lbl">Contact person</div>
                    <div class="val">{{ $customer->contact_person ?: '—' }}</div>
                </div>
                <div class="info-item mb-3">
                    <div class="lbl">Mobile</div>
                    <div class="val">
                        @if($customer->mobile)
                            <a href="tel:{{ preg_replace('/\s+/', '', $customer->mobile) }}">{{ $customer->mobile }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
                <div class="info-item">
                    <div class="lbl">Email</div>
                    <div class="val">
                        @if($customer->email)
                            <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="panel mb-3">
    <div class="panel-head">
        <h2><i class="bi bi-geo me-1"></i> Addresses</h2>
        <span class="text-muted small">{{ $customer->addresses->count() }} on file</span>
    </div>
    <div class="panel-body">
        @if($customer->addresses->isEmpty())
            <div class="empty-state"><i class="bi bi-geo-alt"></i>No addresses captured yet.</div>
        @else
            <div class="row g-3">
                @foreach($customer->addresses as $address)
                    <div class="col-md-6">
                        <div class="addr-card">
                            <div class="type">
                                <i class="bi bi-{{ $address->type === 'shipping' ? 'truck' : 'building' }}"></i>
                                {{ \Illuminate\Support\Str::headline($address->type) }}
                                @if($address->is_default)<span class="chip">Default</span>@endif
                                @if($address->label)<span class="chip">{{ $address->label }}</span>@endif
                            </div>
                            <div class="lines">{{ $address->fullAddress() }}</div>
                            <div class="text-muted small mt-2">
                                State code {{ $address->state_code ?: '—' }}
                                @if($address->state) · GST state {{ $address->state->name }}@endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

<div class="panel">
    <div class="panel-head">
        <h2><i class="bi bi-receipt-cutoff me-1"></i> Recent invoices</h2>
        @if(auth()->user()->hasPermission('invoices.view'))
            <a class="btn btn-sm btn-outline-primary" href="{{ route('invoices.index', ['q' => $customer->legal_name]) }}">View all</a>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 invoice-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Due</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->invoices as $invoice)
                    <tr>
                        <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                        <td>{{ \App\Helpers\IndianFormat::date($invoice->invoice_date) }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($invoice->grand_total) }}</td>
                        <td class="text-end {{ (float) $invoice->balance_due > 0 ? 'text-danger' : '' }}">{{ \App\Helpers\IndianFormat::money($invoice->balance_due) }}</td>
                        <td>@include('partials.status', ['status' => $invoice->status])</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="empty-state mb-0">
                                <i class="bi bi-inbox"></i>
                                No invoices for this customer yet.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
