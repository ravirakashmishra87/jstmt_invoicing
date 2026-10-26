@extends('layouts.app')
@section('title', 'Customers')
@section('page', 'Customers')
@section('subtitle', 'Customer master')

@section('content')
<div class="list-hero">
    <div class="kicker">Master data</div>
    <h1>Customers</h1>
    <p>Manage buyer accounts, GST details, credit limits, and contacts used on quotations and invoices.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Search name, GSTIN, code">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
    </form>
    @if(auth()->user()->hasPermission('customers.create'))
        <a class="btn btn-primary" href="{{ route('customers.create') }}"><i class="bi bi-plus-lg"></i> New customer</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Customer list</h2>
        <span class="text-muted small">{{ $customers->total() }} record{{ $customers->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>GSTIN</th>
                    <th>City</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $customer)
                    @php $address = $customer->addresses->firstWhere('is_default', true) ?? $customer->addresses->first(); @endphp
                    <tr>
                        <td class="text-muted">{{ $customer->customer_code }}</td>
                        <td>
                            <div class="title">{{ $customer->legal_name }}</div>
                            @if($customer->trade_name && $customer->trade_name !== $customer->legal_name)
                                <div class="sub">{{ $customer->trade_name }}</div>
                            @endif
                        </td>
                        <td>{{ $customer->gstin ?: '—' }}</td>
                        <td>{{ $address->city ?? '—' }}</td>
                        <td>{{ $customer->mobile ?: '—' }}</td>
                        <td>
                            <span class="status-pill {{ $customer->is_active ? 'on' : 'off' }}">
                                <i class="bi bi-{{ $customer->is_active ? 'check-circle' : 'slash-circle' }}"></i>
                                {{ $customer->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('customers.view') ? route('customers.show', $customer) : null,
                                'editUrl' => auth()->user()->hasPermission('customers.edit') ? route('customers.edit', $customer) : null,
                                'deleteUrl' => auth()->user()->hasPermission('customers.delete') ? route('customers.destroy', $customer) : null,
                                'deleteConfirm' => 'Archive this customer?',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7"><div class="list-empty"><i class="bi bi-buildings"></i>No customers yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($customers->hasPages())
    <div class="mt-3">{{ $customers->links() }}</div>
@endif
@endsection
