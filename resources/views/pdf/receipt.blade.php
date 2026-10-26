<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt - {{ $receipt->receipt_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111827; }
        table { width: 100%; border-collapse: collapse; }
        .header { border-bottom: 2px solid #1e3a8a; padding-bottom: 8px; margin-bottom: 14px; }
        .title { font-size: 14pt; font-weight: bold; color: #1e3a8a; }
        .muted { color: #6b7280; font-size: 8.5pt; }
        .box { border: 1px solid #d1d5db; padding: 10px; background: #f9fafb; margin-bottom: 10px; }
        .label { color: #6b7280; font-size: 8pt; text-transform: uppercase; letter-spacing: .06em; }
        .value { font-weight: bold; font-size: 11pt; margin-top: 2px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px 8px; font-size: 9pt; }
        .table th { background: #e5e7eb; text-align: left; }
        .right { text-align: right; }
        .amount { font-size: 16pt; font-weight: bold; color: #1e3a8a; }
    </style>
</head>
<body>
@php $money = fn ($n) => \App\Helpers\IndianFormat::number($n); @endphp
<div class="header">
    <div class="title">{{ strtoupper($company->name ?? 'JST MACHINE TOOLS') }}</div>
    <div class="muted">
        {{ collect([$company->address_line1 ?? null, $company->city ?? null, $company->pin ?? null])->filter()->implode(', ') }}
        @if($company?->gstin)<br>GSTIN: {{ $company->gstin }}@endif
    </div>
</div>

<div style="font-size:12pt;font-weight:bold;margin-bottom:10px;color:#1e3a8a;">PAYMENT RECEIPT</div>

<table style="margin-bottom:12px;">
    <tr>
        <td width="50%">
            <div class="box">
                <div class="label">Received from</div>
                <div class="value">{{ $receipt->customer->legal_name ?? 'Customer' }}</div>
                @if($receipt->customer?->gstin)<div class="muted">GSTIN: {{ $receipt->customer->gstin }}</div>@endif
            </div>
        </td>
        <td width="50%">
            <div class="box">
                <div><span class="label">Receipt No.</span> <strong>{{ $receipt->receipt_number }}</strong></div>
                <div style="margin-top:4px;"><span class="label">Date</span> {{ \App\Helpers\IndianFormat::date($receipt->receipt_date) }}</div>
                <div style="margin-top:4px;"><span class="label">Mode</span> {{ $receipt->paymentMode->name ?? '—' }}</div>
                @if($receipt->reference_no)<div style="margin-top:4px;"><span class="label">Reference</span> {{ $receipt->reference_no }}</div>@endif
            </div>
        </td>
    </tr>
</table>

<div class="box" style="text-align:center;margin-bottom:14px;">
    <div class="label">Amount received</div>
    <div class="amount">₹ {{ $money($receipt->amount) }}</div>
    <div class="muted" style="margin-top:4px;">{{ $amountInWords }}</div>
</div>

@if($receipt->allocations->isNotEmpty())
    <table class="table">
        <thead>
            <tr>
                <th>Allocated to</th>
                <th class="right">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receipt->allocations as $allocation)
                <tr>
                    <td>{{ $allocation->invoice->invoice_number ?? ($allocation->allocation_type ?: 'Allocation') }}</td>
                    <td class="right">{{ $money($allocation->amount) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

@if($receipt->remarks)
    <div style="margin-top:12px;"><strong>Remarks:</strong> {{ $receipt->remarks }}</div>
@endif

<div style="margin-top:36px;text-align:right;">
    <div>For {{ strtoupper($company->name ?? 'JST MACHINE TOOLS') }}</div>
    <div style="height:40px;"></div>
    <div style="border-top:1px solid #111;display:inline-block;padding-top:4px;min-width:160px;">Authorised Signatory</div>
</div>
</body>
</html>
