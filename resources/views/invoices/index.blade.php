@extends('layouts.app')
@section('title', 'Invoices')
@section('page', 'Invoices')
@section('subtitle', 'Tax invoices')

@section('content')
<div class="list-hero">
    <div class="kicker">Billing</div>
    <h1>Invoices</h1>
    <p>GST tax invoices, payment status, and print-ready documents.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Number, customer, GSTIN">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['draft', 'issued', 'partially_paid', 'paid', 'cancelled'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    @if(auth()->user()->hasPermission('invoices.create'))
        <a class="btn btn-primary" href="{{ route('invoices.create') }}"><i class="bi bi-plus-lg"></i> New invoice</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Invoice list</h2>
        <span class="text-muted small">{{ $invoices->total() }} record{{ $invoices->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th class="text-end">Due</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td><div class="title">{{ $invoice->invoice_number }}</div></td>
                        <td>{{ \App\Helpers\IndianFormat::date($invoice->invoice_date) }}</td>
                        <td>{{ $invoice->buyer_name }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($invoice->grand_total) }}</td>
                        <td class="text-end {{ (float) $invoice->balance_due > 0 ? 'text-danger' : '' }}">{{ \App\Helpers\IndianFormat::money($invoice->balance_due) }}</td>
                        <td>@include('partials.status', ['status' => $invoice->status])</td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('invoices.view') ? route('invoices.show', $invoice) : null,
                                'editUrl' => ($invoice->isEditable() && auth()->user()->hasPermission('invoices.edit')) ? route('invoices.edit', $invoice) : null,
                                'deleteUrl' => null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="list-empty"><i class="bi bi-receipt"></i>No invoices yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($invoices->hasPages())
    <div class="mt-3">{{ $invoices->links() }}</div>
@endif
@endsection
