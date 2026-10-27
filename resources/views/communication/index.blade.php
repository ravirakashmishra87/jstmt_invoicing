@extends('layouts.app')
@section('title', 'Communication')
@section('page', 'Communication')
@section('subtitle', 'Email centre')

@section('content')
<div class="list-hero">
    <div class="kicker">Client outreach</div>
    <h1>Communication</h1>
    <p>Send quotations, proforma invoices, tax invoices, receipts, or general messages to clients with professional email templates and PDF attachments.</p>
</div>

@unless($smtpReady)
    <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>SMTP is not active yet. Configure it under Settings → Email / SMTP before sending.</span>
        @if(auth()->user()->hasPermission('settings.view'))
            <a class="btn btn-sm btn-outline-dark" href="{{ route('settings.index', ['tab' => 'email']) }}">Open SMTP settings</a>
        @endif
    </div>
@endunless

<div class="list-toolbar">
    <form class="search-wrap" method="GET">
        <input class="form-control" name="q" value="{{ $search }}" placeholder="Search recipient or subject">
        <select name="status" class="form-select" style="min-width:8rem;">
            <option value="">All statuses</option>
            @foreach(['sent', 'failed', 'queued'] as $option)
                <option value="{{ $option }}" @selected($status === $option)>{{ \Illuminate\Support\Str::headline($option) }}</option>
            @endforeach
        </select>
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
    </form>
    <div class="d-flex flex-wrap gap-2">
        @if(auth()->user()->hasPermission('communication.edit') || auth()->user()->hasPermission('settings.edit'))
            <a class="btn btn-outline-secondary" href="{{ route('communication.templates') }}"><i class="bi bi-file-earmark-richtext"></i> Templates</a>
        @endif
        @if(auth()->user()->hasPermission('communication.create'))
            <a class="btn btn-primary" href="{{ route('communication.compose') }}"><i class="bi bi-send"></i> Compose email</a>
        @endif
    </div>
</div>

<div class="list-panel">
    <div class="list-panel-head">
        <h2>Sent &amp; failed mail</h2>
        <span class="text-muted small">{{ $logs->total() }} record{{ $logs->total() === 1 ? '' : 's' }}</span>
    </div>
    <div class="table-responsive">
        <table class="table list-table align-middle mb-0">
            <thead>
                <tr>
                    <th>When</th>
                    <th>To</th>
                    <th>Subject</th>
                    <th>Document</th>
                    <th>Status</th>
                    <th>By</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-muted">{{ $log->created_at?->format('d M Y H:i') }}</td>
                        <td>
                            <div class="title">{{ $log->to_email }}</div>
                            @if($log->cc_email)<div class="sub">CC {{ $log->cc_email }}</div>@endif
                        </td>
                        <td>
                            <div class="title" style="font-weight:600;">{{ $log->subject }}</div>
                            @if($log->attachment_name)<div class="sub"><i class="bi bi-paperclip"></i> {{ $log->attachment_name }}</div>@endif
                            @if($log->error)<div class="sub text-danger">{{ \Illuminate\Support\Str::limit($log->error, 80) }}</div>@endif
                        </td>
                        <td>
                            @if($log->document_type && $log->document_type !== 'general')
                                <span class="status-pill on">{{ \Illuminate\Support\Str::headline($log->document_type) }}</span>
                            @else
                                <span class="text-muted">General</span>
                            @endif
                        </td>
                        <td>
                            @php
                                $tone = match ($log->status) {
                                    'sent' => 'on',
                                    'failed' => 'off',
                                    default => 'on',
                                };
                            @endphp
                            <span class="status-pill {{ $tone }}">{{ \Illuminate\Support\Str::headline($log->status) }}</span>
                        </td>
                        <td class="text-muted">{{ $log->creator->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6"><div class="list-empty"><i class="bi bi-envelope-open"></i>No emails sent yet.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@if($logs->hasPages())
    <div class="mt-3">{{ $logs->links() }}</div>
@endif
@endsection
