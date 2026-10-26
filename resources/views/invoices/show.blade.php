@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('page', $invoice->invoice_number)
@section('subtitle', $invoice->buyer_name.' · '.\App\Helpers\IndianFormat::date($invoice->invoice_date))
@section('content')
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
    @include('partials.back-to-list', ['url' => route('invoices.index'), 'label' => 'Back to invoices'])
    @include('partials.status', ['status' => $invoice->status])
    @if($invoice->isEditable() && auth()->user()->hasPermission('invoices.edit'))
        <a class="btn btn-outline-primary" href="{{ route('invoices.edit', $invoice) }}">Edit draft</a>
    @endif
    @if(auth()->user()->hasPermission('invoices.print'))
        <a class="btn btn-outline-secondary" target="_blank" href="{{ route('invoices.pdf', $invoice) }}">PDF</a>
    @endif
    @include('partials.email-document', ['type' => 'invoice', 'id' => $invoice->id])
    @if($invoice->status !== 'cancelled' && (float) $invoice->amount_paid <= 0 && auth()->user()->hasPermission('invoices.approve') && $invoice->status !== 'draft')
        <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice and reverse stock?')">
            @csrf
            <button class="btn btn-outline-danger">Cancel invoice</button>
        </form>
    @endif
    @if($invoice->status === 'draft' && auth()->user()->hasPermission('invoices.approve'))
        <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this draft?')">
            @csrf
            <button class="btn btn-outline-danger">Discard draft</button>
        </form>
    @endif
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Bill to</div>
        <div class="fw-semibold">{{ $invoice->buyer_name }}</div>
        <div>{{ $invoice->buyer_address }}</div>
        <div>GSTIN {{ $invoice->buyer_gstin ?: '—' }} · {{ $invoice->buyer_state }} ({{ $invoice->buyer_state_code }})</div>
    </div></div></div>
    <div class="col-md-6"><div class="card h-100"><div class="card-body">
        <div class="text-muted small">Ship to / supply</div>
        <div class="fw-semibold">{{ $invoice->consignee_name }}</div>
        <div>{{ $invoice->consignee_address }}</div>
        <div>Place of supply {{ $invoice->place_of_supply }} ({{ $invoice->place_of_supply_code }}) · {{ strtoupper($invoice->tax_type) }}</div>
        @if($invoice->quotation)<div class="small">From quotation {{ $invoice->quotation->quotation_number }}</div>@endif
    </div></div></div>
</div>

<div class="card mb-3"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Description</th><th>HSN</th><th class="text-end">Qty</th><th class="text-end">Rate</th><th class="text-end">Amount</th><th class="text-end">Tax</th></tr></thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->sr_no }}</td>
                    <td>
                        {{ $item->description }}
                        @if($item->secondary_description)<div class="small text-muted">{{ $item->secondary_description }}</div>@endif
                        @if($item->serials->isNotEmpty())
                            <div class="small">Serial: {{ $item->serials->pluck('serial_number')->implode(', ') }}</div>
                        @endif
                    </td>
                    <td>{{ $item->hsn_sac }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::number($item->quantity, 2) }} {{ $item->uom }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->rate) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($item->tax_amount) }}</td>
                </tr>
            @endforeach
            @foreach($invoice->charges as $charge)
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
        @if($invoice->taxSummary->isNotEmpty())
            <div class="card mb-3"><div class="card-header bg-white">HSN summary</div><div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>HSN/SAC</th><th class="text-end">Taxable</th><th class="text-end">Rate</th><th class="text-end">CGST</th><th class="text-end">SGST</th><th class="text-end">IGST</th></tr></thead>
                    <tbody>
                        @foreach($invoice->taxSummary as $row)
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
        <div class="card"><div class="card-body">{{ $invoice->amount_in_words }}</div></div>
    </div>
    <div class="col-lg-5">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between"><span>Taxable</span><strong>{{ \App\Helpers\IndianFormat::money($invoice->taxable_amount) }}</strong></div>
            <div class="d-flex justify-content-between"><span>CGST</span><span>{{ \App\Helpers\IndianFormat::money($invoice->cgst_amount) }}</span></div>
            <div class="d-flex justify-content-between"><span>SGST</span><span>{{ \App\Helpers\IndianFormat::money($invoice->sgst_amount) }}</span></div>
            <div class="d-flex justify-content-between"><span>IGST</span><span>{{ \App\Helpers\IndianFormat::money($invoice->igst_amount) }}</span></div>
            <div class="d-flex justify-content-between"><span>Round off</span><span>{{ \App\Helpers\IndianFormat::money($invoice->round_off) }}</span></div>
            <div class="d-flex justify-content-between border-top pt-2 mt-2"><span>Grand total</span><strong>{{ \App\Helpers\IndianFormat::money($invoice->grand_total) }}</strong></div>
            <div class="d-flex justify-content-between"><span>Paid</span><span>{{ \App\Helpers\IndianFormat::money($invoice->amount_paid) }}</span></div>
            <div class="d-flex justify-content-between"><span>Balance</span><strong>{{ \App\Helpers\IndianFormat::money($invoice->balance_due) }}</strong></div>
        </div></div>
    </div>
</div>

@if($invoice->status === 'draft' && auth()->user()->hasPermission('invoices.approve'))
    <form method="POST" action="{{ route('invoices.issue', $invoice) }}" class="card mt-3">
        @csrf
        <div class="card-header bg-white fw-semibold">Issue invoice</div>
        <div class="card-body">
            <p class="text-muted">Issuing locks the invoice number, deducts stock, and marks selected serials as sold.</p>
            @foreach($invoice->items as $item)
                @if($item->product && $item->product->track_serial)
                    @php $needed = (int) round((float) $item->quantity); @endphp
                    <div class="mb-3">
                        <div class="fw-semibold">{{ $item->description }} — select {{ $needed }} serial{{ $needed === 1 ? '' : 's' }}</div>
                        @forelse($item->product->serials as $serial)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="serials[{{ $item->id }}][]" value="{{ $serial->id }}" id="serial-{{ $item->id }}-{{ $serial->id }}">
                                <label class="form-check-label" for="serial-{{ $item->id }}-{{ $serial->id }}">{{ $serial->serial_number }}</label>
                            </div>
                        @empty
                            <div class="text-danger small">No in-stock serials. Add opening stock before issuing.</div>
                        @endforelse
                    </div>
                @endif
            @endforeach
            <button class="btn btn-primary">Issue and lock number</button>
        </div>
    </form>
@endif
@endsection
