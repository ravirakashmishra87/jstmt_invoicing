@extends('layouts.app')
@section('title', $meta['title'])
@section('page', 'Masters')
@section('subtitle', $meta['title'])

@push('styles')
<style>
    .masters-hero {
        position: relative;
        overflow: hidden;
        border-radius: 16px;
        background:
            radial-gradient(1000px 240px at 100% -30%, rgba(142, 197, 239, .26), transparent 55%),
            linear-gradient(135deg, #0f2744 0%, #16365c 50%, #1e4e79 100%);
        color: #e8f1f8;
        padding: 1.25rem 1.4rem;
        margin-bottom: 1.1rem;
        box-shadow: 0 18px 40px rgba(15, 39, 68, .16);
    }
    .masters-hero .kicker {
        font-size: .72rem;
        letter-spacing: .14em;
        text-transform: uppercase;
        color: #93b4d0;
        font-weight: 600;
        margin-bottom: .2rem;
    }
    .masters-hero h1 {
        font-family: Barlow, sans-serif;
        font-size: clamp(1.25rem, 2vw, 1.65rem);
        font-weight: 700;
        color: #fff;
        margin: 0;
    }
    .masters-hero p {
        margin: .35rem 0 0;
        color: #b7cee3;
        max-width: 36rem;
    }
    .masters-hero .btn-ghost {
        background: transparent;
        border: 1px solid rgba(255,255,255,.28);
        color: #fff;
        border-radius: 10px;
        font-weight: 600;
    }
    .masters-hero .btn-ghost:hover {
        background: rgba(255,255,255,.1);
        color: #fff;
    }
    .master-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        overflow: hidden;
        margin-bottom: 1rem;
    }
    .master-panel-head {
        padding: .95rem 1.15rem;
        border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .master-panel-head h2 {
        margin: 0;
        font-size: .98rem;
        font-weight: 700;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .master-panel-body { padding: 1.15rem; }
    .master-panel .form-label {
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .3rem;
    }
    .master-panel .form-control,
    .master-panel .form-select {
        border-radius: 10px;
        border-color: #d5dee8;
        min-height: 40px;
    }
    .master-panel .form-control:focus,
    .master-panel .form-select:focus {
        border-color: #2f6fa3;
        box-shadow: 0 0 0 .2rem rgba(47, 111, 163, .15);
    }
    .master-table thead th {
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        color: #64748b;
        background: #f8fafc;
        border-bottom-color: var(--line);
        white-space: nowrap;
        padding: .75rem 1rem;
    }
    .master-table tbody td {
        padding: .85rem 1rem;
        vertical-align: middle;
        border-color: #edf2f7;
    }
    .master-table tbody tr:hover { background: #f8fafc; }
    .record-title {
        font-weight: 700;
        color: var(--ink);
    }
    .record-sub {
        color: #64748b;
        font-size: .86rem;
        margin-top: .15rem;
    }
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        padding: .22rem .55rem;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
    }
    .status-pill.on {
        background: #ecfdf5;
        color: #047857;
        border: 1px solid #a7f3d0;
    }
    .status-pill.off {
        background: #fef2f2;
        color: #b91c1c;
        border: 1px solid #fecaca;
    }
    .icon-actions {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }
    .icon-btn {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        display: inline-grid;
        place-items: center;
        border: 1px solid var(--line);
        background: #fff;
        color: var(--steel);
        text-decoration: none;
        transition: background .12s ease, border-color .12s ease, color .12s ease;
        padding: 0;
        line-height: 1;
    }
    .icon-btn:hover {
        background: #eef5fb;
        border-color: #9db8d1;
        color: var(--ink);
    }
    .icon-btn.danger {
        color: #b91c1c;
    }
    .icon-btn.danger:hover {
        background: #fef2f2;
        border-color: #fca5a5;
        color: #991b1b;
    }
    .empty-state {
        text-align: center;
        padding: 2.2rem 1rem;
        color: #64748b;
    }
    .empty-state i {
        font-size: 1.5rem;
        color: #94a3b8;
        display: block;
        margin-bottom: .45rem;
    }
    .editing-banner {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        border-radius: 10px;
        padding: .65rem .85rem;
        font-size: .88rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }
</style>
@endpush

@section('content')
<div class="masters-hero">
    <div class="d-flex flex-wrap justify-content-between gap-3 align-items-start">
        <div class="d-flex gap-3 align-items-start">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,.12);display:grid;place-items:center;font-size:1.2rem;">
                <i class="bi bi-{{ $meta['icon'] ?? 'collection' }}"></i>
            </div>
            <div>
                <div class="kicker">Master data</div>
                <h1>{{ $meta['title'] }}</h1>
                <p>{{ $meta['hint'] }}</p>
            </div>
        </div>
        @include('partials.back-to-list', ['url' => route('masters.index'), 'label' => 'All masters', 'class' => 'btn btn-ghost'])
    </div>
</div>

@if($view && ! $edit)
<div class="master-panel">
    <div class="master-panel-head">
        <h2><i class="bi bi-eye"></i> View record</h2>
        <div class="d-flex gap-2">
            @if(auth()->user()->hasPermission('masters.edit'))
                <a class="btn btn-sm btn-primary" href="{{ route('masters.list', ['type' => $type, 'edit' => $view->id]) }}"><i class="bi bi-pencil-square"></i> Edit</a>
            @endif
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('masters.list', $type) }}">Close</a>
        </div>
    </div>
    <div class="master-panel-body">
        @php
            $viewRows = match ($type) {
                'uoms' => [
                    'Code' => $view->code,
                    'Name' => $view->name,
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                'tax-rates' => [
                    'Name' => $view->name,
                    'Rate' => \App\Helpers\IndianFormat::number($view->rate, 2).'%',
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                'hsn' => [
                    'Type' => $view->type,
                    'Code' => $view->code,
                    'Description' => $view->description ?: '—',
                    'Tax rate' => optional($view->taxRate)->name ?: '—',
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                'payment-terms' => [
                    'Name' => $view->name,
                    'Advance %' => \App\Helpers\IndianFormat::number($view->advance_percent, 0).'%',
                    'Balance %' => \App\Helpers\IndianFormat::number($view->balance_percent, 0).'%',
                    'Due days' => $view->due_days,
                    'Milestone' => $view->milestone ?: '—',
                    'Description' => $view->description ?: '—',
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                'charge-types' => [
                    'Code' => $view->code,
                    'Name' => $view->name,
                    'Taxable' => ($view->is_taxable ?? true) ? 'Yes' : 'No',
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                'transporters' => [
                    'Name' => $view->name,
                    'GSTIN' => $view->gstin ?: '—',
                    'Transporter ID' => $view->transporter_id ?: '—',
                    'Phone' => $view->phone ?: '—',
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                'categories' => [
                    'Name' => $view->name,
                    'Parent' => $view->parent->name ?? 'Top level',
                    'Sort' => $view->sort_order,
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
                default => [
                    'Name' => $view->name,
                    'Status' => ($view->is_active ?? true) ? 'Active' : 'Inactive',
                ],
            };
        @endphp
        <div class="row g-3">
            @foreach($viewRows as $label => $value)
                <div class="col-md-4">
                    <div class="text-muted small text-uppercase fw-bold mb-1">{{ $label }}</div>
                    <div class="fw-semibold">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif

@if(($edit && auth()->user()->hasPermission('masters.edit')) || (! $edit && ! $view && auth()->user()->hasPermission('masters.create')))
<div class="master-panel">
    <div class="master-panel-head">
        <h2>
            <i class="bi bi-{{ $edit ? 'pencil-square' : 'plus-circle' }}"></i>
            {{ $edit ? 'Edit record' : 'Add new record' }}
        </h2>
        @if($edit)
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('masters.list', $type) }}">Cancel edit</a>
        @endif
    </div>
    <div class="master-panel-body">
        @if($edit)
            <div class="editing-banner">
                <i class="bi bi-info-circle"></i>
                You are editing an existing {{ \Illuminate\Support\Str::lower($meta['title']) }} record.
            </div>
        @endif
        <form method="POST" action="{{ $edit ? route('masters.update', [$type, $edit->id]) : route('masters.store', $type) }}">
            @csrf
            @if($edit) @method('PUT') @endif
            <div class="row g-3 align-items-end">
                @include('masters._fields', ['row' => $edit])
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-check2-circle"></i>
                        {{ $edit ? 'Update' : 'Save' }}
                    </button>
                    @if($edit)
                        <a class="btn btn-outline-secondary" href="{{ route('masters.list', $type) }}">Cancel</a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>
@endif

<div class="master-panel">
    <div class="master-panel-head">
        <h2><i class="bi bi-list-ul"></i> {{ $meta['title'] }} list</h2>
        <span class="text-muted small">{{ $records->total() }} record{{ $records->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table master-table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Record</th>
                    <th style="width:120px;">Status</th>
                    <th class="text-end" style="width:140px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    @php
                        $title = match ($type) {
                            'uoms' => $record->code.' — '.$record->name,
                            'tax-rates' => $record->name,
                            'hsn' => $record->code,
                            'payment-terms' => $record->name,
                            'charge-types' => $record->code.' — '.$record->name,
                            'transporters' => $record->name,
                            default => $record->name,
                        };
                        $subtitle = match ($type) {
                            'tax-rates' => \App\Helpers\IndianFormat::number($record->rate, 2).'% GST',
                            'hsn' => trim(($record->type ?? '').' · '.($record->description ?? ''), ' ·'),
                            'payment-terms' => \App\Helpers\IndianFormat::number($record->advance_percent, 0).'% advance · '.$record->due_days.' days',
                            'charge-types' => ($record->is_taxable ?? true) ? 'Taxable charge' : 'Non-taxable charge',
                            'transporters' => collect([$record->phone, $record->gstin])->filter()->implode(' · '),
                            'categories' => $record->parent?->name ? 'Under '.$record->parent->name : 'Top-level category',
                            default => null,
                        };
                        $isActive = (bool) ($record->is_active ?? true);
                        $isEditing = $edit && (int) $edit->id === (int) $record->id;
                        $isViewing = $view && (int) $view->id === (int) $record->id;
                    @endphp
                    <tr class="{{ $isEditing || $isViewing ? 'table-primary' : '' }}">
                        <td>
                            <div class="record-title">{{ $title }}</div>
                            @if($subtitle)
                                <div class="record-sub">{{ $subtitle }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="status-pill {{ $isActive ? 'on' : 'off' }}">
                                <i class="bi bi-{{ $isActive ? 'check-circle' : 'slash-circle' }}"></i>
                                {{ $isActive ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @include('partials.row-actions', [
                                'viewUrl' => auth()->user()->hasPermission('masters.view') ? route('masters.list', ['type' => $type, 'view' => $record->id]) : null,
                                'editUrl' => auth()->user()->hasPermission('masters.edit') ? route('masters.list', ['type' => $type, 'edit' => $record->id]) : null,
                                'deleteUrl' => auth()->user()->hasPermission('masters.delete') ? route('masters.destroy', [$type, $record->id]) : null,
                                'deleteConfirm' => 'Delete this record?',
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3">
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                No records yet. Add the first one above.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($records->hasPages())
    <div class="mt-3">{{ $records->links() }}</div>
@endif
@endsection
