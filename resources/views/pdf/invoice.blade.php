<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_number }}</title>
    @include('pdf._styles')
    <style>
        /* Tax invoice: standard commercial print sizes (8pt body) */
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
@endphp
@if($invoice->status === 'cancelled')<div class="flag">CANCELLED</div>@endif
@if($invoice->status === 'draft')<div class="draft">DRAFT — NOT A TAX INVOICE</div>@endif
<div class="banner">TAX INVOICE</div>
<div class="sub">{{ $invoice->supply_type }} · {{ strtoupper(str_replace('_', ' ', $invoice->tax_type)) }} · Original for recipient</div>

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
                <tr><td class="muted">Invoice No.</td><td class="strong">{{ $invoice->invoice_number }}</td></tr>
                <tr><td class="muted">Date</td><td>{{ $date($invoice->invoice_date) }}</td></tr>
                <tr><td class="muted">Place of supply</td><td>{{ $invoice->place_of_supply }} ({{ $invoice->place_of_supply_code }})</td></tr>
                <tr><td class="muted">Reverse charge</td><td>{{ $invoice->reverse_charge ? 'Yes' : 'No' }}</td></tr>
                <tr><td class="muted">Buyer order</td><td>{{ $invoice->buyer_order_no }} {{ $date($invoice->buyer_order_date) }}</td></tr>
                <tr><td class="muted">Vehicle / LR</td><td>{{ $invoice->vehicle_no }} {{ $invoice->lr_rr_no }}</td></tr>
                <tr><td class="muted">Payment</td><td>{{ $invoice->mode_terms_of_payment }}</td></tr>
            </table>
        </td>
    </tr>
    <tr>
        <td>
            <div class="muted">Bill to</div>
            <div class="strong">{{ $invoice->buyer_name }}</div>
            <div>{{ $invoice->buyer_address }}</div>
            <div>GSTIN: {{ $invoice->buyer_gstin ?: 'URP' }}</div>
            <div>State: {{ $invoice->buyer_state }} &nbsp; Code: {{ $invoice->buyer_state_code }}</div>
            <div>{{ $invoice->buyer_contact }}</div>
        </td>
        <td>
            <div class="muted">Ship to</div>
            <div class="strong">{{ $invoice->consignee_name }}</div>
            <div>{{ $invoice->consignee_address }}</div>
            <div>GSTIN: {{ $invoice->consignee_gstin ?: '—' }}</div>
            <div>State: {{ $invoice->consignee_state }} &nbsp; Code: {{ $invoice->consignee_state_code }}</div>
            <div>Destination: {{ $invoice->destination }}</div>
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
            <th width="8%" class="right">Disc</th>
            <th width="12%" class="right">Taxable</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->items as $item)
            <tr>
                <td class="center">{{ $item->sr_no }}</td>
                <td>
                    @include('partials.invoice-item-copy', ['item' => $item, 'showSecondary' => true])
                    @if($item->serials->isNotEmpty())<br><span class="muted">Sr. {{ $item->serials->pluck('serial_number')->implode(', ') }}</span>@endif
                </td>
                <td class="center">{{ $item->hsn_sac }}</td>
                <td class="right">{{ \App\Helpers\IndianFormat::number($item->quantity, 2) }}</td>
                <td class="center">{{ $item->uom }}</td>
                <td class="right">{{ $money($item->rate) }}</td>
                <td class="right">{{ $money($item->discount_amount) }}</td>
                <td class="right">{{ $money($item->amount) }}</td>
            </tr>
        @endforeach
        @foreach($invoice->charges as $charge)
            <tr>
                <td></td>
                <td>{{ $charge->name }}</td>
                <td class="center">{{ $charge->hsn_sac }}</td>
                <td></td><td></td><td></td><td></td>
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
        @foreach($invoice->taxSummary as $row)
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
            <div class="amount-words">{{ $invoice->amount_in_words }}</div>
            <div class="muted amount-words" style="margin-top: 4px;">Tax amount: {{ $invoice->tax_amount_in_words }}</div>
        </td>
        <td>
            <table class="totals-table">
                <tr><td>Taxable value</td><td class="right">{{ $money($invoice->taxable_amount) }}</td></tr>
                <tr><td>CGST</td><td class="right">{{ $money($invoice->cgst_amount) }}</td></tr>
                <tr><td>SGST</td><td class="right">{{ $money($invoice->sgst_amount) }}</td></tr>
                <tr><td>IGST</td><td class="right">{{ $money($invoice->igst_amount) }}</td></tr>
                <tr><td>Round off</td><td class="right">{{ $money($invoice->round_off) }}</td></tr>
                <tr><td class="strong">Grand total</td><td class="right strong">₹ {{ $money($invoice->grand_total) }}</td></tr>
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
@if($seller && (float) $invoice->grand_total >= (float) $seller->eway_bill_threshold)
    <div class="footer-note" style="margin-top: 4px;">E-way bill is applicable above ₹ {{ $money($seller->eway_bill_threshold) }}.</div>
@endif
<div class="center footer-note" style="margin-top: 6px;">{{ $seller->footer_text ?? '' }}</div>
</body>
</html>
