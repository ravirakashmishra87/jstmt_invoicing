<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $proforma->proforma_number }}</title>
    @include('pdf._styles')
    <style>
        .amount-words { font-size: 8pt; line-height: 1.35; }
        .totals-table td { font-size: 8pt; padding: 2px 0; }
        .totals-table .strong { font-size: 9pt; }
        .footer-note { font-size: 7pt; color: #4b5563; }
        .item-desc p { margin: 0 0 2px; }
        .item-desc ul, .item-desc ol { margin: 2px 0 2px 14px; padding: 0; }
        .item-desc li { margin: 0 0 1px; }
    </style>
</head>
<body>
@php
    $seller = $company;
    $money = fn ($amount) => \App\Helpers\IndianFormat::number($amount);
    $date = fn ($value) => \App\Helpers\IndianFormat::date($value);
    $showDiscount = $proforma->items->contains(fn ($item) => (float) $item->discount_percent > 0 || (float) $item->discount_amount > 0);
@endphp
@if($proforma->status === 'cancelled')<div class="flag">CANCELLED</div>@endif
@if($proforma->status === 'draft')<div class="draft">DRAFT PROFORMA</div>@endif
<div class="banner">PROFORMA INVOICE</div>
<div class="sub">{{ $proforma->supply_type }} | {{ strtoupper(str_replace('_', ' ', $proforma->tax_type)) }} | This is not a tax invoice</div>

<table class="box">
    <tr>
        <td width="58%">
            <h1>{{ $seller->name ?? 'JST Machine Tools' }}</h1>
            <div>{{ $seller->trade_name ?? '' }}</div>
            <div>{{ $seller->address_line1 ?? '' }} {{ $seller->address_line2 ?? '' }}</div>
            <div>{{ $seller->city ?? '' }} {{ $seller->district ?? '' }} {{ $seller->state->name ?? '' }} {{ $seller->pin ?? '' }}</div>
            <div>GSTIN: <span class="strong">{{ $seller->gstin ?? '' }}</span> &nbsp; PAN: {{ $seller->pan ?? '' }}</div>
            <div>State: {{ $seller->state->name ?? '' }} &nbsp; Code: {{ $seller->state_code ?? '' }}</div>
            <div>{{ $seller->phone ?? '' }} {{ $seller->email ?? '' }}</div>
        </td>
        <td width="42%">
            <table>
                <tr><td class="muted">Proforma No.</td><td class="strong">{{ $proforma->proforma_number }}</td></tr>
                <tr><td class="muted">Date</td><td>{{ $date($proforma->proforma_date) }}</td></tr>
                <tr><td class="muted">Valid until</td><td>{{ $date($proforma->valid_until) }}</td></tr>
                <tr><td class="muted">Place of supply</td><td>{{ $proforma->place_of_supply }} ({{ $proforma->place_of_supply_code }})</td></tr>
                <tr><td class="muted">Reverse charge</td><td>{{ $proforma->reverse_charge ? 'Yes' : 'No' }}</td></tr>
                <tr><td class="muted">Buyer order</td><td>{{ $proforma->buyer_order_no }} {{ $date($proforma->buyer_order_date) }}</td></tr>
                <tr><td class="muted">Payment</td><td>{{ $proforma->mode_terms_of_payment }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <div class="muted">Bill to</div>
            <div class="strong">{{ $proforma->buyer_name }}</div>
            <div>{{ $proforma->buyer_address }}</div>
            <div>GSTIN: {{ $proforma->buyer_gstin ?: 'URP' }}</div>
            <div>State: {{ $proforma->buyer_state }} &nbsp; Code: {{ $proforma->buyer_state_code }}</div>
            <div>{{ $proforma->buyer_contact }}</div>
        </td>
        <td>
            <div class="muted">Ship to</div>
            <div class="strong">{{ $proforma->consignee_name }}</div>
            <div>{{ $proforma->consignee_address }}</div>
            <div>GSTIN: {{ $proforma->consignee_gstin ?: '?' }}</div>
            <div>State: {{ $proforma->consignee_state }} &nbsp; Code: {{ $proforma->consignee_state_code }}</div>
            <div>Destination: {{ $proforma->destination }}</div>
        </td>
    </tr>
</table>

<table class="box" style="margin-top: 6px;">
    <thead>
        <tr>
            <th width="4%">#</th>
            <th>Description</th>
            <th width="10%">HSN/SAC</th>
            <th width="8%" class="right">Qty</th>
            <th width="6%">UoM</th>
            <th width="12%" class="right">Rate</th>
            @if($showDiscount)
                <th width="8%" class="right">Disc %</th>
            @endif
            <th width="12%" class="right">Taxable</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proforma->items as $item)
            <tr>
                <td class="center">{{ $item->sr_no }}</td>
                <td>
                    @include('partials.invoice-item-copy', ['item' => $item, 'showSecondary' => true])
                </td>
                <td class="center">{{ $item->hsn_sac }}</td>
                <td class="right">{{ \App\Helpers\IndianFormat::number($item->quantity, 2) }}</td>
                <td class="center">{{ $item->uom }}</td>
                <td class="right">{{ $money($item->rate) }}</td>
                @if($showDiscount)
                    <td class="right">{{ (float) $item->discount_percent > 0 ? \App\Helpers\IndianFormat::number($item->discount_percent, 2).'%' : '—' }}</td>
                @endif
                <td class="right">{{ $money($item->amount) }}</td>
            </tr>
        @endforeach
        @foreach($proforma->charges as $charge)
            <tr>
                <td></td>
                <td>{{ $charge->name }}</td>
                <td class="center">{{ $charge->hsn_sac }}</td>
                <td></td><td></td><td></td>
                @if($showDiscount)<td></td>@endif
                <td class="right">{{ $money($charge->amount) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="box" style="margin-top: 6px;">
    <thead>
        <tr>
            <th>HSN/SAC</th>
            <th class="right">Taxable</th>
            <th class="right">Rate</th>
            <th class="right">CGST</th>
            <th class="right">SGST</th>
            <th class="right">IGST</th>
            <th class="right">Total tax</th>
        </tr>
    </thead>
    <tbody>
        @foreach($proforma->taxSummary as $row)
            <tr>
                <td>{{ $row->hsn_sac }}</td>
                <td class="right">{{ $money($row->taxable_value) }}</td>
                <td class="right">{{ \App\Helpers\IndianFormat::number($row->tax_rate, 2) }}%</td>
                <td class="right">{{ $money($row->cgst_amount) }}</td>
                <td class="right">{{ $money($row->sgst_amount) }}</td>
                <td class="right">{{ $money($row->igst_amount) }}</td>
                <td class="right">{{ $money($row->tax_amount) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="box" style="margin-top: 6px;">
    <tr>
        <td width="62%">
            <div class="strong">Amount in words</div>
            <div class="amount-words">{{ $proforma->amount_in_words }}</div>
            <div class="muted amount-words" style="margin-top: 4px;">Tax amount: {{ $proforma->tax_amount_in_words }}</div>
        </td>
        <td>
            <table class="totals-table">
                <tr><td>Taxable value</td><td class="right">{{ $money($proforma->taxable_amount) }}</td></tr>
                <tr><td>CGST</td><td class="right">{{ $money($proforma->cgst_amount) }}</td></tr>
                <tr><td>SGST</td><td class="right">{{ $money($proforma->sgst_amount) }}</td></tr>
                <tr><td>IGST</td><td class="right">{{ $money($proforma->igst_amount) }}</td></tr>
                <tr><td>Round off</td><td class="right">{{ $money($proforma->round_off) }}</td></tr>
                <tr><td class="strong">Grand total</td><td class="right strong">&#8377; {{ $money($proforma->grand_total) }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            @if($bank)
                <div class="strong">Bank details</div>
                <div>{{ $bank->bank_name }} {{ $bank->branch }}</div>
                <div>A/c {{ $bank->account_number }} &nbsp; IFSC {{ $bank->ifsc }}</div>
                <div>{{ $bank->account_holder }}</div>
            @endif
            <div style="margin-top: 6px;">{{ $seller->declaration ?? '' }}</div>
        </td>
        <td>
            <div class="right">For {{ $seller->name ?? 'JST Machine Tools' }}</div>
            <div class="sign"></div>
            <div class="right strong">Authorised Signatory</div>
            <div class="right muted">{{ $seller->jurisdiction ? 'Subject to '.$seller->jurisdiction.' jurisdiction' : '' }}</div>
        </td>
    </tr>
</table>
<div class="footer-note" style="margin-top: 6px;">This proforma invoice is for commercial reference only and does not constitute a tax invoice under GST.</div>
<div class="center footer-note" style="margin-top: 4px;">{{ $seller->footer_text ?? '' }}</div>
</body>
</html>
