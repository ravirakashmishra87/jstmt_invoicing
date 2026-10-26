@extends('layouts.app')
@section('title', 'Quotations')
@section('page', 'Quotations')
@section('subtitle', 'Sales pipeline')

@section('content')
<div class="list-hero">
    <div class="kicker">Sales</div>
    <h1>Quotations</h1>
    <p>Techno-commercial offers, revisions, and conversion to invoices.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Number or customer">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['draft', 'sent', 'under_negotiation', 'accepted', 'rejected', 'expired', 'revised'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    @if(auth()->user()->hasPermission('quotations.create'))
        <a class="btn btn-primary" href="{{ route('quotations.create') }}"><i class="bi bi-plus-lg"></i> New quotation</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Quotation list</h2>
        <span class="text-muted small">{{ $quotations->total() }} record{{ $quotations->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($quotations as $quotation)
                    <tr>
                        <td><div class="title">{{ $quotation->quotation_number }}</div></td>
                        <td>{{ \App\Helpers\IndianFormat::date($quotation->quotation_date) }}</td>
                        <td>{{ $quotation->customer_name }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($quotation->grand_total) }}</td>
                        <td>@include('partials.status', ['status' => $quotation->status])</td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('quotations.view') ? route('quotations.show', $quotation) : null,
                                'editUrl' => auth()->user()->hasPermission('quotations.edit') ? route('quotations.edit', $quotation) : null,
                                'deleteUrl' => null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="list-empty"><i class="bi bi-file-earmark-text"></i>No quotations yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($quotations->hasPages())
    <div class="mt-3">{{ $quotations->links() }}</div>
@endif
@endsection
