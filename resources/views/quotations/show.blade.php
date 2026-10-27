@extends('layouts.app')
@section('title', $quotation->quotation_number)
@section('page', $quotation->quotation_number)
@section('subtitle', $quotation->customer_name)
@push('styles')
<style>
    .item-desc p { margin: 0 0 .2rem; }
    .item-desc ul, .item-desc ol { margin: .15rem 0 .15rem 1.1rem; padding: 0; }
</style>
@endpush

@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    @include('partials.back-to-list', ['url' => route('quotations.index'), 'label' => 'Back to quotations'])
    @if(auth()->user()->hasPermission('quotations.edit'))
        <a class="btn btn-outline-primary" href="{{ route('quotations.edit', $quotation) }}">Edit</a>
    @endif
    @if(auth()->user()->hasPermission('quotations.print'))
        <a class="btn btn-outline-secondary" target="_blank" href="{{ route('quotations.pdf', $quotation) }}">PDF</a>
    @endif
    @include('partials.email-document', ['type' => 'quotation', 'id' => $quotation->id])
    @if(auth()->user()->hasPermission('quotations.create'))
        <form method="POST" action="{{ route('quotations.duplicate', $quotation) }}">@csrf<button class="btn btn-outline-secondary">Duplicate</button></form>
    @endif
    @if(auth()->user()->hasPermission('proformas.create') && ! $hasProforma && ! in_array($quotation->status, ['rejected', 'expired']))
        <form method="POST" action="{{ route('quotations.convert-proforma', $quotation) }}">@csrf<button class="btn btn-outline-primary">Convert to proforma</button></form>
    @endif
    @if(auth()->user()->hasPermission('invoices.create') && ! $hasInvoice && ! in_array($quotation->status, ['rejected', 'expired']))
        <form method="POST" action="{{ route('quotations.convert', $quotation) }}">@csrf<button class="btn btn-primary">Convert to invoice</button></form>
    @endif
</div>
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-body">
            <div class="fw-semibold">{{ $quotation->subject }}</div>
            <div>{{ $quotation->customer_address }}</div>
            <div class="text-muted small">GSTIN {{ $quotation->customer_gstin ?: '—' }} · Place of supply {{ $quotation->place_of_supply_state }} ({{ $quotation->place_of_supply_code }})</div>
            <div class="small">Valid until {{ \App\Helpers\IndianFormat::date($quotation->valid_until) }} · {{ strtoupper($quotation->tax_type) }}</div>
        </div></div>
    </div>
    <div class="col-lg-4">
        @if(auth()->user()->hasPermission('quotations.edit'))
            <form method="POST" action="{{ route('quotations.status', $quotation) }}" class="card"><div class="card-body">
                @csrf
                <label class="form-label">Status</label>
                <select name="status" class="form-select mb-2">
                    @foreach(['draft', 'sent', 'under_negotiation', 'accepted', 'rejected', 'expired', 'revised'] as $option)
                        <option value="{{ $option }}" @selected($quotation->status === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>
                    @endforeach
                </select>
                <input name="win_loss_reason" class="form-control mb-2" placeholder="Win / loss reason" value="{{ $quotation->win_loss_reason }}">
                <button class="btn btn-primary btn-sm">Update status</button>
            </div></form>
        @else
            <div class="card"><div class="card-body">@include('partials.status', ['status' => $quotation->status])</div></div>
        @endif
    </div>
</div>
<div class="card mb-3"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Item</th><th>HSN</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-end">GST</th></tr></thead>
        <tbody>
            @foreach($quotation->items as $item)
                <tr>
                    <td>{{ $item->sr_no }}</td>
                    <td>
                        <div class="fw-semibold">{{ $item->product_name }}</div>
                        @if($item->model_number)<div class="small text-muted">Model: {{ $item->model_number }}</div>@endif
                        @if($item->description)
                            <div class="small text-muted item-desc mt-1">{!! nl2br(e(\App\Support\RichText::plain($item->description))) !!}</div>
                        @endif
                    </td>
                    <td>{{ $item->hsn_sac }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($item->quantity, 0) }} {{ $item->uom }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->rate) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($item->tax_rate, 0) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div></div>

@if($quotation->charges->isNotEmpty())
<div class="card mb-3">
    <div class="card-header bg-white fw-semibold">Accessories and charges</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Description</th>
                    <th>HSN</th>
                    <th class="text-end">Qty</th>
                    <th>Unit</th>
                    <th class="text-end">Rate</th>
                    <th class="text-end">GST</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotation->charges as $charge)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $charge->name }} @if($charge->is_at_actual)<span class="text-muted">at actual</span>@endif</td>
                        <td>{{ $charge->hsn_sac ?: '—' }}</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::number($charge->quantity ?? 1, 0) }}</td>
                        <td>{{ $charge->uom ?: '—' }}</td>
                        <td class="text-end">{{ $charge->is_at_actual ? '—' : \App\Helpers\IndianFormat::money($charge->rate) }}</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::number($charge->tax_rate, 0) }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card mb-3"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <tfoot>
            <tr><th colspan="5" class="text-end">Taxable</th><th class="text-end">{{ \App\Helpers\IndianFormat::money($quotation->taxable_amount) }}</th><th></th></tr>
            @if((float) $quotation->igst_amount > 0)
                <tr><th colspan="5" class="text-end">IGST</th><th class="text-end">{{ \App\Helpers\IndianFormat::money($quotation->igst_amount) }}</th><th></th></tr>
            @else
                <tr><th colspan="5" class="text-end">CGST</th><th class="text-end">{{ \App\Helpers\IndianFormat::money($quotation->cgst_amount) }}</th><th></th></tr>
                <tr><th colspan="5" class="text-end">SGST</th><th class="text-end">{{ \App\Helpers\IndianFormat::money($quotation->sgst_amount) }}</th><th></th></tr>
            @endif
            <tr><th colspan="5" class="text-end">Grand total</th><th class="text-end">{{ \App\Helpers\IndianFormat::money($quotation->grand_total) }}</th><th></th></tr>
        </tfoot>
    </table>
</div></div>
<div class="card"><div class="card-body">
    <div class="fw-semibold">{{ $amountInWords }}</div>
    @if($quotation->terms_content)
        <pre class="mt-3 mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $quotation->terms_content }}</pre>
    @endif
</div></div>
@endsection
