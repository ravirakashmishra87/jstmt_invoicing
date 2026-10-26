@extends('layouts.app')
@section('title', 'Outstanding')
@section('page', 'Outstanding')
@section('content')
@include('reports._filters')
<div class="card"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>Invoice</th><th>Customer</th><th>Due</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Balance</th><th class="text-end">Overdue days</th></tr></thead>
        <tbody>
            @forelse($rows as $invoice)
                @php
                    $overdue = ($invoice->due_date && $invoice->due_date->copy()->endOfDay()->isPast())
                        ? (int) $invoice->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay())
                        : 0;
                @endphp
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                    <td>{{ $invoice->buyer_name }}</td>
                    <td>{{ \App\Helpers\IndianFormat::date($invoice->due_date) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($invoice->grand_total) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($invoice->amount_paid) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($invoice->balance_due) }}</td>
                    <td class="text-end">{{ $overdue }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted p-3">Nothing outstanding.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot><tr><th colspan="5">Total due</th><th class="text-end">{{ \App\Helpers\IndianFormat::money($rows->sum('balance_due')) }}</th><th></th></tr></tfoot>
        @endif
    </table>
</div></div>
@endsection
