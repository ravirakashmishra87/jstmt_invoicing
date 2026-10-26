@extends('layouts.app')
@section('title', 'Receipts')
@section('page', 'Receipts')
@section('subtitle', 'Collections')

@section('content')
<div class="list-hero">
    <div class="kicker">Receivables</div>
    <h1>Receipts</h1>
    <p>Customer payments and allocations against invoices.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Receipt, reference, customer">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
    </form>
    @if(auth()->user()->hasPermission('receipts.create'))
        <a class="btn btn-primary" href="{{ route('receipts.create') }}"><i class="bi bi-plus-lg"></i> New receipt</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Receipt list</h2>
        <span class="text-muted small">{{ $receipts->total() }} record{{ $receipts->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Mode</th>
                    <th class="text-end">Amount</th>
                    <th class="text-end">Unallocated</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipts as $receipt)
                    <tr>
                        <td><div class="title">{{ $receipt->receipt_number }}</div></td>
                        <td>{{ \App\Helpers\IndianFormat::date($receipt->receipt_date) }}</td>
                        <td>{{ $receipt->customer->legal_name ?? '—' }}</td>
                        <td>{{ $receipt->paymentMode->name ?? '—' }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($receipt->amount) }}</td>
                        <td class="text-end">{{ \App\Helpers\IndianFormat::money($receipt->unallocated_amount) }}</td>
                        <td>@include('partials.status', ['status' => $receipt->status])</td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('receipts.view') ? route('receipts.show', $receipt) : null,
                                'editUrl' => null,
                                'deleteUrl' => null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="list-empty"><i class="bi bi-cash-coin"></i>No receipts yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($receipts->hasPages())
    <div class="mt-3">{{ $receipts->links() }}</div>
@endif
@endsection
