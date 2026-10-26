@extends('layouts.app')
@section('title', 'Sales register')
@section('page', 'Sales register')
@section('content')
@include('reports._filters')
<div class="card"><div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead><tr><th>Invoice</th><th>Date</th><th>Customer</th><th>Status</th><th class="text-end">Taxable</th><th class="text-end">Tax</th><th class="text-end">Total</th></tr></thead>
        <tbody>
            @forelse($rows as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_number }}</a></td>
                    <td>{{ \App\Helpers\IndianFormat::date($invoice->invoice_date) }}</td>
                    <td>{{ $invoice->buyer_name }}</td>
                    <td>@include('partials.status', ['status' => $invoice->status])</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($invoice->taxable_amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money((float) $invoice->cgst_amount + (float) $invoice->sgst_amount + (float) $invoice->igst_amount) }}</td>
                    <td class="text-end">{{ \App\Helpers\IndianFormat::money($invoice->grand_total) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-muted p-3">No invoices in this range.</td></tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
            <tfoot>
                <tr>
                    <th colspan="4">Total</th>
                    <th class="text-end">{{ \App\Helpers\IndianFormat::money($rows->sum('taxable_amount')) }}</th>
                    <th class="text-end">{{ \App\Helpers\IndianFormat::money($rows->sum('cgst_amount') + $rows->sum('sgst_amount') + $rows->sum('igst_amount')) }}</th>
                    <th class="text-end">{{ \App\Helpers\IndianFormat::money($rows->sum('grand_total')) }}</th>
                </tr>
            </tfoot>
        @endif
    </table>
</div></div>
@endsection
