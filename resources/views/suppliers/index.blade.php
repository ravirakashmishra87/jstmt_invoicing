@extends('layouts.app')
@section('title', 'Suppliers')
@section('page', 'Suppliers')
@section('subtitle', 'Supplier master')

@section('content')
<div class="list-hero">
    <div class="kicker">Master data</div>
    <h1>Suppliers</h1>
    <p>Maintain vendor accounts, GSTIN, and contacts for purchase and stock receipts.</p>
</div>

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Search suppliers">
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Search</button>
    </form>
    @if(auth()->user()->hasPermission('suppliers.create'))
        <a class="btn btn-primary" href="{{ route('suppliers.create') }}"><i class="bi bi-plus-lg"></i> New supplier</a>
    @endif
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Supplier list</h2>
        <span class="text-muted small">{{ $suppliers->total() }} record{{ $suppliers->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>GSTIN</th>
                    <th>Mobile</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($suppliers as $supplier)
                    <tr>
                        <td class="text-muted">{{ $supplier->supplier_code }}</td>
                        <td>
                            <div class="title">{{ $supplier->legal_name }}</div>
                            @if($supplier->trade_name)<div class="sub">{{ $supplier->trade_name }}</div>@endif
                        </td>
                        <td>{{ $supplier->gstin ?: '—' }}</td>
                        <td>{{ $supplier->mobile ?: '—' }}</td>
                        <td>
                            <span class="status-pill {{ $supplier->is_active ? 'on' : 'off' }}">
                                <i class="bi bi-{{ $supplier->is_active ? 'check-circle' : 'slash-circle' }}"></i>
                                {{ $supplier->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('suppliers.view') ? route('suppliers.show', $supplier) : null,
                                'editUrl' => auth()->user()->hasPermission('suppliers.edit') ? route('suppliers.edit', $supplier) : null,
                                'deleteUrl' => auth()->user()->hasPermission('suppliers.delete') ? route('suppliers.destroy', $supplier) : null,
                                'deleteConfirm' => 'Archive this supplier?',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="list-empty"><i class="bi bi-truck"></i>No suppliers yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($suppliers->hasPages())
    <div class="mt-3">{{ $suppliers->links() }}</div>
@endif
@endsection
