@extends('layouts.app')
@section('title', $receipt->receipt_number)
@section('page', $receipt->receipt_number)
@section('subtitle', $receipt->customer->legal_name ?? '')
@section('content')
<div class="d-flex flex-wrap gap-2 mb-3">
    @include('partials.back-to-list', ['url' => route('receipts.index'), 'label' => 'Back to receipts'])
    @include('partials.email-document', ['type' => 'receipt', 'id' => $receipt->id])
</div>
<div class="row g-3">
    <div class="col-md-5"><div class="card"><div class="card-body">
        <div>Date {{ \App\Helpers\IndianFormat::date($receipt->receipt_date) }}</div>
        <div>Mode {{ $receipt->paymentMode->name ?? '—' }}</div>
        <div>Reference {{ $receipt->reference_no ?: '—' }}</div>
        <div>Bank {{ $receipt->bankAccount->bank_name ?? '—' }}</div>
        <div class="mt-2">Amount <strong>{{ \App\Helpers\IndianFormat::money($receipt->amount) }}</strong></div>
        <div>Unallocated {{ \App\Helpers\IndianFormat::money($receipt->unallocated_amount) }}</div>
        @if($receipt->remarks)<div class="text-muted mt-2">{{ $receipt->remarks }}</div>@endif
    </div></div></div>
    <div class="col-md-7"><div class="card"><div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Invoice</th><th class="text-end">Allocated</th><th>Status</th></tr></thead>
            <tbody>
                @forelse($receipt->allocations as $allocation)
                    <tr>
                        <td>@if($allocation->invoice)<a href="{{ route('invoices.show', $allocation->invoice) }}">{{ $allocation->invoice->invoice_number }}</a>@else On account @endif</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::money($allocation->amount) }}</td>
                        <td>@if($allocation->invoice) @include('partials.status', ['status' => $allocation->invoice->status]) @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-muted p-3">Nothing allocated.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div></div></div>
</div>
@endsection
