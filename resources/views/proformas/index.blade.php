@extends('layouts.app')
@section('title', 'Proforma invoices')
@section('page', 'Proforma invoices')
@section('subtitle', 'Commercial proformas')

@section('content')
<div class="list-hero">
    <div class="kicker">Billing</div>
    <h1>Proforma invoices</h1>
    <p>Preliminary commercial invoices for advance and order confirmation — convert to a tax invoice when ready.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Number, customer, GSTIN">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['draft', 'sent', 'converted', 'cancelled'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    @if(auth()->user()->hasPermission('proformas.create'))
        <a class="btn btn-primary" href="{{ route('proformas.create') }}"><i class="bi bi-plus-lg"></i> New proforma</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Proforma list</h2>
        <span class="text-muted small">{{ $proformas->total() }} record{{ $proformas->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-end">Total</th>
                    <th>Valid until</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($proformas as $proforma)
                    <tr>
                        <td><div class="title">{{ $proforma->proforma_number }}</div></td>
                        <td>{{ \App\Helpers\IndianFormat::date($proforma->proforma_date) }}</td>
                        <td>{{ $proforma->buyer_name }}</td>
                        <td class="text-end fw-semibold">{{ \App\Helpers\IndianFormat::money($proforma->grand_total) }}</td>
                        <td>{{ \App\Helpers\IndianFormat::date($proforma->valid_until) }}</td>
                        <td>@include('partials.status', ['status' => $proforma->status])</td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('proformas.view') ? route('proformas.show', $proforma) : null,
                                'editUrl' => ($proforma->isEditable() && auth()->user()->hasPermission('proformas.edit')) ? route('proformas.edit', $proforma) : null,
                                'deleteUrl' => null,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="list-empty"><i class="bi bi-file-earmark-ruled"></i>No proforma invoices yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($proformas->hasPages())
    <div class="mt-3">{{ $proformas->links() }}</div>
@endif
@endsection
