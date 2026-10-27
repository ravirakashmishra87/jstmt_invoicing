@extends('layouts.app')
@section('title', $proforma->proforma_number)
@section('page', $proforma->proforma_number)
@section('subtitle', $proforma->buyer_name.' · '.\App\Helpers\IndianFormat::date($proforma->proforma_date))
@push('styles')
<style>
    .item-desc p { margin: 0 0 .2rem; }
    .item-desc ul, .item-desc ol { margin: .15rem 0 .15rem 1.1rem; padding: 0; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    @include('partials.back-to-list', ['url' => route('proformas.index'), 'label' => 'Back to proformas'])
    @include('partials.status', ['status' => $proforma->status])
    @if($proforma->isEditable() && auth()->user()->hasPermission('proformas.edit'))
        <a class="btn btn-outline-primary" href="{{ route('proformas.edit', $proforma) }}">Edit</a>
    @endif
    @if(auth()->user()->hasPermission('proformas.print'))
        <a class="btn btn-outline-secondary" target="_blank" href="{{ route('proformas.pdf', $proforma) }}">PDF</a>
    @endif
    @include('partials.email-document', ['type' => 'proforma', 'id' => $proforma->id])
    @if($proforma->status === 'draft' && auth()->user()->hasPermission('proformas.edit'))
        <form method="POST" action="{{ route('proformas.sent', $proforma) }}">
            @csrf
            <button class="btn btn-outline-primary">Mark as sent</button>
        </form>
    @endif
    @if(in_array($proforma->status, ['draft', 'sent'], true) && auth()->user()->hasPermission('invoices.create'))
        <form method="POST" action="{{ route('proformas.convert', $proforma) }}" onsubmit="return confirm('Create a draft tax invoice from this proforma?')">
            @csrf
            <button class="btn btn-primary">Convert to tax invoice</button>
        </form>
    @endif
    @if(! in_array($proforma->status, ['converted', 'cancelled'], true) && auth()->user()->hasPermission('proformas.edit'))
        <form method="POST" action="{{ route('proformas.cancel', $proforma) }}" onsubmit="return confirm('Cancel this proforma?')">
            @csrf
            <button class="btn btn-outline-danger">Cancel</button>
        </form>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Bill to</div>
        <div class="fw-semibold">{{ $proforma->buyer_name }}</div>
        <div>{{ $proforma->buyer_address }}</div>
        <div>GSTIN {{ $proforma->buyer_gstin ?: '—' }} · {{ $proforma->buyer_state }} ({{ $proforma->buyer_state_code }})</div>
    </div></div></div>
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Ship to / supply</div>
        <div class="fw-semibold">{{ $proforma->consignee_name }}</div>
        <div>{{ $proforma->consignee_address }}</div>
        <div>Place of supply {{ $proforma->place_of_supply }} ({{ $proforma->place_of_supply_code }}) · {{ strtoupper($proforma->tax_type) }}</div>
        <div class="small mt-1">Valid until {{ \App\Helpers\IndianFormat::date($proforma->valid_until) ?: '—' }}</div>
        @if($proforma->quotation)<div class="small">From quotation {{ $proforma->quotation->quotation_number }}</div>@endif
        @foreach($proforma->invoices as $linked)
            <div class="small">Linked invoice <a href="{{ route('invoices.show', $linked) }}">{{ $linked->invoice_number }}</a></div>
        @endforeach
    </div></div></div>
</div>

<div class="card mb-3"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Item Description</th><th>HSN</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-end">Tax</th></tr></thead>
        <tbody>
            @foreach($proforma->items as $item)
                <tr>
                    <td>{{ $item->sr_no }}</td>
                    <td>@include('partials.invoice-item-copy', ['item' => $item, 'showSecondary' => true])</td>
                    <td>{{ $item->hsn_sac }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($item->quantity, 2) }} {{ $item->uom }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->rate) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->tax_amount) }}</td>
                </tr>
            @endforeach
            @foreach($proforma->charges as $charge)
                <tr>
                    <td></td>
                    <td>{{ $charge->name }}</td>
                    <td>{{ $charge->hsn_sac }}</td>
                    <td></td><td></td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($charge->amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($charge->tax_amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div>

<div class="row g-3">
    <div class="col-lg-7">
        @if($proforma->taxSummary->isNotEmpty())
            <div class="card mb-3"><div class="card-header bg-white">HSN summary</div><div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>HSN/SAC</th><th class="text-end">Taxable</th><th class="text-end">Rate</th><th class="text-end">CGST</th><th class="text-end">SGST</th><th class="text-end">IGST</th></tr></thead>
                    <tbody>
                        @foreach($proforma->taxSummary as $row)
                            <tr>
                                <td>{{ $row->hsn_sac }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->taxable_value) }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::number($row->tax_rate, 0) }}%</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->cgst_amount) }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->sgst_amount) }}</td>
                                <td class="text-end">{{ \App\Helpers\IndianFormat::money($row->igst_amount) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div></div>
        @endif
        @if($proforma->notes)
            <div class="card"><div class="card-body"><div class="text-muted small">Notes</div><div>{{ $proforma->notes }}</div></div></div>
        @endif
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Taxable</span><strong>{{ \App\Helpers\IndianFormat::money($proforma->taxable_amount) }}</strong></div>
            <div class="d-flex justify-content-between"><span>CGST</span><span>{{ \App\Helpers\IndianFormat::money($proforma->cgst_amount) }}</span></div>
            <div class="d-flex justify-content-between"><span>SGST</span><span>{{ \App\Helpers\IndianFormat::money($proforma->sgst_amount) }}</span></div>
            <div class="d-flex justify-content-between"><span>IGST</span><span>{{ \App\Helpers\IndianFormat::money($proforma->igst_amount) }}</span></div>
            <div class="d-flex justify-content-between"><span>Round off</span><span>{{ \App\Helpers\IndianFormat::money($proforma->round_off) }}</span></div>
            <hr>
            <div class="d-flex justify-content-between fs-5"><span>Grand total</span><strong>{{ \App\Helpers\IndianFormat::money($proforma->grand_total) }}</strong></div>
            <div class="small text-muted mt-2">{{ $proforma->amount_in_words }}</div>
        </div></div>
    </div>
</div>
@endsection
