<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm 10mm 12mm 10mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8.5pt;
            color: #111827;
            line-height: 1.35;
        }
        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; padding: 4px 6px; }
        .bold { font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .header-bar { border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 12px; }
        .company-title { font-size: 14pt; font-weight: bold; color: #1e3a8a; letter-spacing: .4px; }
        .company-sub { font-size: 8pt; color: #4b5563; }
        .brand-badge {
            background: #1e3a8a; color: #fff; font-weight: bold; font-size: 11pt;
            padding: 5px 12px; display: inline-block;
        }
        .box {
            border: 1px solid #d1d5db; padding: 8px; margin-bottom: 8px; background: #f9fafb;
        }
        .box-green { background: #f0fdf4; border-color: #bbf7d0; }
        .section-header {
            background: #1e3a8a; color: #fff; font-weight: bold; font-size: 8.5pt;
            padding: 5px 8px; margin-top: 10px; margin-bottom: 0;
        }
        .spec-table th, .spec-table td,
        .offer-table th, .offer-table td,
        .terms-table th, .terms-table td {
            border: 1px solid #d1d5db; font-size: 8pt; padding: 5px 6px;
        }
        .spec-table td:first-child { width: 40%; font-weight: bold; background: #f8fafc; }
        .offer-table th { background: #e5e7eb; text-align: center; border-color: #1f2937; }
        .offer-table td { border-color: #1f2937; }
        .subject {
            margin: 8px 0; padding: 5px 8px; background: #e0e7ff;
            border-left: 4px solid #1e3a8a; font-weight: bold; font-size: 9pt;
        }
        .product-photo { max-width: 220px; max-height: 160px; margin: 8px 0; }
        .total-row td { background: #f3f4f6; font-weight: bold; }
        .grand td { color: #1e3a8a; font-size: 10pt; }
        .page-break { page-break-before: always; }
        .muted { color: #6b7280; }
        .item-desc p { margin: 0 0 2px; }
        .item-desc ul, .item-desc ol { margin: 2px 0 2px 14px; padding: 0; }
    </style>
</head>
<body>
@php
    $seller = $company;
    $money = fn ($n) => \App\Helpers\IndianFormat::number($n);
    $date = fn ($d) => \App\Helpers\IndianFormat::date($d);
    $validityDays = $quotation->quotation_date && $quotation->valid_until
        ? $quotation->quotation_date->diffInDays($quotation->valid_until)
        : ($seller->quotation_validity_days ?? 7);
    $city = $seller->city ?: 'Ahmedabad';
    $showDiscount = $quotation->items->contains(fn ($item) => (float) $item->discount_percent > 0 || (float) $item->discount_amount > 0);
@endphp

<table class="header-bar">
    <tr>
        <td width="70%">
            <div class="company-title">{{ strtoupper($seller->name ?? 'JST MACHINE TOOLS') }}</div>
            <div class="company-sub">
                {{ collect([$seller->address_line1, $seller->address_line2, $seller->city ? $seller->city.' - '.$seller->pin : null, $seller->state->name ?? null])->filter()->implode(', ') }}<br>
                <span class="bold">GSTIN:</span> {{ $seller->gstin ?? '—' }}
                | <span class="bold">Email:</span> {{ $seller->email ?? '—' }}
                | <span class="bold">Phone:</span> {{ $seller->phone ?? '—' }}
                @if($seller->website)<br><span class="bold">Website:</span> {{ $seller->website }}@endif
            </div>
        </td>
        <td width="30%" class="right">
            <div class="brand-badge">JST MACHINES</div>
            <div style="font-size:7.5pt;color:#6b7280;margin-top:4px;">Precision Metal Cutting Solutions</div>
        </td>
    </tr>
</table>

<table style="margin-bottom:8px;">
    <tr>
        <td width="55%">
            <div class="box">
                <span class="bold" style="color:#1e3a8a;text-decoration:underline;">Customer Details:</span><br>
                <div class="bold" style="font-size:9.5pt;margin-top:2px;">{{ $quotation->customer_name }}</div>
                <div>{!! nl2br(e($quotation->customer_address)) !!}</div>
                <div><span class="bold">GSTIN:</span> {{ $quotation->customer_gstin ?: '—' }}</div>
                <div>
                    <span class="bold">Kind Attn:</span>
                    {{ $quotation->customer->contact_person ?? 'Purchase In-charge' }}
                    @if($quotation->customer_mobile) ({{ $quotation->customer_mobile }})@endif
                </div>
            </div>
        </td>
        <td width="45%">
            <div class="box box-green">
                <div><span class="bold">Quotation Offer No.:</span> <span class="bold" style="color:#166534;font-size:9.5pt;">{{ $quotation->quotation_number }}</span></div>
                <div><span class="bold">Date of Offer:</span> {{ $date($quotation->quotation_date) }}</div>
                <div><span class="bold">Validity:</span> {{ $validityDays }} Days (Expires: {{ $date($quotation->valid_until) }})</div>
                <div><span class="bold">Prepared By:</span> {{ $quotation->salesperson->name ?? 'Sales Department' }}</div>
                <div><span class="bold">Contact:</span> {{ $quotation->salesperson->phone ?? $seller->phone ?? '—' }}</div>
            </div>
        </td>
    </tr>
</table>

@if($quotation->subject)
    <div class="subject">Subject: {{ $quotation->subject }}</div>
@endif

<div style="font-size:8pt;margin-bottom:8px;">
    Dear Sir/Madam,<br>
    We thank you very much for your valued enquiry. We take pleasure in submitting our lowest techno-commercial offer for your kind consideration:
</div>

@if($quotation->show_images && !empty($productImages))
    <div class="center" style="margin:8px 0 10px;">
        @foreach($productImages as $imagePath)
            <img src="{{ $imagePath }}" class="product-photo" alt="Product">
        @endforeach
    </div>
@endif

@foreach($specGroups as $group)
    <div class="section-header">{{ $group['name'] }}</div>
    <table class="spec-table" style="margin-bottom:6px;">
        <tbody>
            @foreach($group['items'] as $item)
                <tr>
                    <td>{{ $item['label'] !== '' ? $item['label'] : '—' }}</td>
                    <td>{{ $item['value'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endforeach

@if(!empty($components))
    <div class="section-header">Component and Brand</div>
    <table class="spec-table" style="margin-bottom:6px;">
        <tbody>
            @foreach($components as $component)
                <tr>
                    <td>{{ $component['component_name'] }}</td>
                    <td>{{ $component['brand_name'] ?: '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

<div class="page-break"></div>

@php $offerLabelColspan = $showDiscount ? 6 : 5; @endphp
<div class="section-header">COMMERCIAL OFFER &amp; PRICE SCHEDULE</div>
<table class="offer-table">
    <thead>
        <tr>
            <th width="6%">Sr. No.</th>
            <th width="{{ $showDiscount ? '32%' : '38%' }}">Model / Description</th>
            <th width="12%">HSN/SAC</th>
            <th width="10%">Qty</th>
            <th width="{{ $showDiscount ? '12%' : '16%' }}">Rate (₹)</th>
            @if($showDiscount)
                <th width="10%">Disc %</th>
            @endif
            <th width="{{ $showDiscount ? '14%' : '16%' }}">Amount (₹)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($quotation->items as $item)
            <tr>
                <td class="center">{{ $item->sr_no }}</td>
                <td>
                    <span class="bold" style="font-size:9pt;">{{ $item->product_name }}</span>
                    @if($item->model_number)<div><span class="bold">Model:</span> {{ $item->model_number }}</div>@endif
                    @if($item->description)
                        <div class="muted item-desc" style="font-size:7.5pt;margin-top:2px;">{!! nl2br(e(\App\Support\RichText::plain($item->description))) !!}</div>
                    @endif
                </td>
                <td class="center">{{ $item->hsn_sac }}</td>
                <td class="center bold">{{ \App\Helpers\IndianFormat::number($item->quantity, 0) }} {{ $item->uom ?: 'NOS' }}</td>
                <td class="right">{{ $money($item->rate) }}</td>
                @if($showDiscount)
                    <td class="right">{{ (float) $item->discount_percent > 0 ? \App\Helpers\IndianFormat::number($item->discount_percent, 2).'%' : '—' }}</td>
                @endif
                <td class="right bold">{{ $money($item->amount) }}</td>
            </tr>
        @endforeach
        @foreach($quotation->charges as $charge)
            @php
                $chargeQty = (float) ($charge->quantity ?? 1);
                if ($chargeQty <= 0) {
                    $chargeQty = 1;
                }
                $chargeRate = (float) ($charge->rate ?? 0);
                if ($chargeRate <= 0 && (float) $charge->amount > 0) {
                    $chargeRate = (float) $charge->amount / $chargeQty;
                }
            @endphp
            <tr>
                <td class="center">{{ $quotation->items->count() + $loop->iteration }}</td>
                <td>
                    <div class="muted" style="font-size:7pt;letter-spacing:.04em;text-transform:uppercase;margin-bottom:1px;">Accessories / Charges</div>
                    <span class="bold" style="font-size:9pt;">{{ $charge->name }}</span>
                    @if($charge->is_at_actual)
                        <div class="muted" style="font-size:7.5pt;margin-top:2px;">{{ $charge->at_actual_text ?: 'At actual / on your account' }}</div>
                    @endif
                </td>
                <td class="center">{{ $charge->hsn_sac ?: '—' }}</td>
                <td class="center bold">{{ \App\Helpers\IndianFormat::number($chargeQty, 0) }} {{ $charge->uom ?: 'NOS' }}</td>
                <td class="right">{{ $charge->is_at_actual ? '—' : $money($chargeRate) }}</td>
                @if($showDiscount)<td class="right">—</td>@endif
                <td class="right bold">{{ $charge->is_at_actual ? '—' : $money($charge->amount) }}</td>
            </tr>
        @endforeach
        <tr>
            <td colspan="{{ $offerLabelColspan }}" class="right bold">Taxable Value:</td>
            <td class="right bold">₹ {{ $money($quotation->taxable_amount) }}</td>
        </tr>
        @if($quotation->tax_type === 'igst')
            <tr>
                <td colspan="{{ $offerLabelColspan }}" class="right bold">Add: Integrated GST (IGST @ {{ $gstLabel }}%):</td>
                <td class="right bold">₹ {{ $money($quotation->igst_amount) }}</td>
            </tr>
        @else
            <tr>
                <td colspan="{{ $offerLabelColspan }}" class="right bold">Add: CGST @ {{ $halfGstLabel }}%:</td>
                <td class="right bold">₹ {{ $money($quotation->cgst_amount) }}</td>
            </tr>
            <tr>
                <td colspan="{{ $offerLabelColspan }}" class="right bold">Add: SGST @ {{ $halfGstLabel }}%:</td>
                <td class="right bold">₹ {{ $money($quotation->sgst_amount) }}</td>
            </tr>
        @endif
        @if(abs((float) $quotation->round_off) > 0)
            <tr>
                <td colspan="{{ $offerLabelColspan }}" class="right bold">Round Off:</td>
                <td class="right bold">₹ {{ $money($quotation->round_off) }}</td>
            </tr>
        @endif
        <tr class="total-row grand">
            <td colspan="{{ $offerLabelColspan }}" class="right">Total Value (Ex-works {{ $city }}):</td>
            <td class="right">₹ {{ $money($quotation->grand_total) }}</td>
        </tr>
    </tbody>
</table>

<div style="padding:4px 0 8px;">
    <div><span class="bold">Amount in Words:</span> {{ $amountInWords }}</div>
    @if(filled($quotation->notes))
        <div style="margin-top:4px;"><span class="bold">Notes:</span> {!! nl2br(e($quotation->notes)) !!}</div>
    @endif
</div>

<div class="section-header">COMMERCIAL TERMS &amp; CONDITIONS</div>
<table class="terms-table" style="margin-bottom:10px;">
    @forelse($termsRows as $term)
        <tr>
            <td width="25%" class="bold" style="background:#f8fafc;">{{ $term['title'] }}:</td>
            <td width="75%">{{ $term['content'] }}</td>
        </tr>
    @empty
        <tr>
            <td colspan="2">{!! nl2br(e($quotation->terms_content ?: 'As mutually agreed.')) !!}</td>
        </tr>
    @endforelse
</table>

<table>
    <tr>
        <td width="60%">
            @if($quotation->show_bank_details && $bank)
                <div class="box">
                    <div class="bold" style="color:#1e3a8a;text-decoration:underline;">Direct Bank Transfer (NEFT / RTGS):</div>
                    <div><span class="bold">Bank Name:</span> {{ $bank->bank_name }}</div>
                    <div><span class="bold">Beneficiary Name:</span> {{ $bank->account_holder }}</div>
                    <div><span class="bold">Current A/c No.:</span> {{ $bank->account_number }}</div>
                    <div><span class="bold">IFSC Code:</span> {{ $bank->ifsc }} @if($bank->branch)| <span class="bold">Branch:</span> {{ $bank->branch }}@endif</div>
                </div>
            @endif
        </td>
        <td width="40%" class="center">
            <div class="bold">for {{ strtoupper($seller->name ?? 'JST MACHINE TOOLS') }}</div>
            <div style="height:48px;"></div>
            <div class="bold" style="border-top:1px solid #1f2937;margin-top:4px;padding-top:2px;">Authorised Signatory</div>
        </td>
    </tr>
</table>
</body>
</html>
