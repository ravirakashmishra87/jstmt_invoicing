@extends('layouts.app')
@section('title', 'Email templates')
@section('page', 'Email templates')
@section('subtitle', 'Communication')

@push('styles')
<style>
    .tpl-grid {
        display: grid;
        gap: 1rem;
    }
    .tpl-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        box-shadow: 0 10px 28px rgba(15, 39, 68, .05);
        overflow: hidden;
    }
    .tpl-card-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        padding: 1rem 1.2rem;
        border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, #f7fafc 0%, #fff 100%);
    }
    .tpl-card-head h2 {
        margin: 0;
        font-size: 1.05rem;
        color: var(--ink);
    }
    .tpl-card-head .muted {
        color: #64748b;
        font-weight: 500;
        font-size: .86rem;
    }
    .tpl-layout {
        display: grid;
        grid-template-columns: minmax(0, 1.05fr) minmax(280px, .95fr);
        gap: 0;
    }
    @media (max-width: 1099.98px) {
        .tpl-layout { grid-template-columns: 1fr; }
    }
    .tpl-form {
        padding: 1.15rem 1.2rem 1.3rem;
        border-right: 1px solid var(--line);
    }
    @media (max-width: 1099.98px) {
        .tpl-form { border-right: 0; border-bottom: 1px solid var(--line); }
    }
    .tpl-form .form-label {
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
    }
    .tpl-form .form-control,
    .tpl-form textarea {
        border-radius: 10px;
    }
    .tpl-preview-wrap {
        background: linear-gradient(180deg, #dfeaf4 0%, #e7eef5 100%);
        padding: 1rem;
        min-height: 100%;
    }
    .tpl-preview-label {
        font-size: .72rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        font-weight: 700;
        color: #475569;
        margin-bottom: .65rem;
    }
    .email-preview-frame { max-width: 520px; margin: 0 auto; }
    .email-preview-shell {
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 16px 40px rgba(15, 39, 68, .16);
        background: #fff;
    }
    .email-preview-masthead {
        display: flex;
        gap: .75rem;
        align-items: center;
        padding: 1.15rem 1.25rem 1rem;
        background: linear-gradient(135deg, #0f2744 0%, #16365c 55%, #1e4e79 100%);
        color: #fff;
    }
    .email-preview-mark {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        background: #2f6fa3;
        display: grid;
        place-items: center;
        font-family: Barlow, sans-serif;
        font-weight: 700;
        letter-spacing: .04em;
        flex-shrink: 0;
    }
    .email-preview-brand {
        font-family: Barlow, sans-serif;
        font-weight: 700;
        font-size: 1rem;
        line-height: 1.2;
    }
    .email-preview-tag {
        margin-top: .2rem;
        font-size: .68rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #93a9c0;
    }
    .email-preview-accent { height: 4px; background: #2f6fa3; }
    .email-preview-body { padding: 1.15rem 1.25rem 1.2rem; background: #fff; }
    .email-preview-pill {
        display: inline-block;
        padding: .28rem .7rem;
        border-radius: 999px;
        background: #eef5fb;
        border: 1px solid #c5d7ea;
        color: #1e4e79;
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: .9rem;
    }
    .email-preview-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .55rem;
        background: #f4f8fc;
        border: 1px solid #d5dee8;
        border-radius: 12px;
        padding: .8rem;
        margin-bottom: 1rem;
    }
    .email-preview-meta-item span {
        display: block;
        font-size: .62rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .2rem;
    }
    .email-preview-meta-item strong {
        color: var(--ink);
        font-family: Barlow, sans-serif;
        font-size: .88rem;
        word-break: break-word;
    }
    .email-preview-copy {
        color: #334155;
        font-size: .92rem;
        line-height: 1.65;
        white-space: pre-wrap;
        min-height: 4.5rem;
    }
    .email-preview-attach {
        margin-top: .9rem;
        padding: .7rem .85rem;
        border-radius: 10px;
        border: 1px dashed #9db8d1;
        background: #f8fbfe;
        color: #16365c;
        font-size: .82rem;
    }
    .email-preview-attach.d-none,
    .email-preview-meta.d-none { display: none !important; }
    .email-preview-signoff {
        margin-top: 1rem;
        padding-top: .9rem;
        border-top: 1px solid #d5dee8;
        color: #64748b;
        font-size: .8rem;
    }
    .email-preview-foot {
        background: #0f2744;
        color: #93a9c0;
        text-align: center;
        font-size: .72rem;
        padding: .75rem 1rem;
        line-height: 1.45;
    }
    .placeholder-chip {
        display: inline-flex;
        align-items: center;
        gap: .25rem;
        margin: .15rem .2rem .15rem 0;
        padding: .2rem .45rem;
        border-radius: 6px;
        background: #e8eef5;
        color: var(--ink);
        font-size: .74rem;
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    }
</style>
@endpush

@section('content')
<div class="list-hero">
    <div class="kicker">Communication</div>
    <h1>Email templates</h1>
    <p>Craft the subject and body for each message type. Recipients see a branded HTML layout with your company identity.</p>
</div>

<div class="list-toolbar">
    <div class="text-muted small">
        Placeholders:
        <span class="placeholder-chip">@{{customer_name}}</span>
        <span class="placeholder-chip">@{{document_number}}</span>
        <span class="placeholder-chip">@{{document_date}}</span>
        <span class="placeholder-chip">@{{amount}}</span>
        <span class="placeholder-chip">@{{company_name}}</span>
        <span class="placeholder-chip">@{{document_subject}}</span>
        <span class="placeholder-chip">@{{message}}</span>
    </div>
    <a class="btn btn-outline-secondary" href="{{ route('communication.index') }}"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="tpl-grid">
@foreach($templates as $template)
    @php
        $sampleVars = [
            'customer_name' => 'Acme Engineering',
            'document_number' => match ($template->document_type) {
                'quotation' => 'QT-2026-001',
                'proforma' => 'PI/26-27/001',
                'invoice' => 'INV-2026-001',
                'receipt' => 'RCP-2026-001',
                default => '',
            },
            'document_date' => now()->format('d-m-Y'),
            'amount' => '₹ 1,25,000.00',
            'company_name' => $companyName,
            'document_subject' => 'CNC Vertical Machining Centre',
            'message' => 'Sharing an update regarding your recent enquiry with our team.',
        ];
        $sampleBody = $template->renderBody($sampleVars);
        $sampleMeta = match ($template->document_type) {
            'quotation', 'proforma', 'invoice', 'receipt' => [
                'Number' => $sampleVars['document_number'],
                'Date' => $sampleVars['document_date'],
                'Amount' => $sampleVars['amount'],
            ],
            default => [],
        };
        $typeLabel = \App\Support\DefaultEmailTemplates::typeLabel($template->document_type);
    @endphp
    <div class="tpl-card" data-template-card>
        <div class="tpl-card-head">
            <div>
                <h2>{{ $template->name }}</h2>
                <div class="muted">{{ \Illuminate\Support\Str::headline($template->document_type) }} · branded HTML delivery</div>
            </div>
            <span class="status-pill {{ $template->is_active ? 'on' : 'off' }}">{{ $template->is_active ? 'Active' : 'Inactive' }}</span>
        </div>
        <div class="tpl-layout">
            <div class="tpl-form">
                <form method="POST" action="{{ route('communication.templates.update', $template) }}">
                    @csrf @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name</label>
                            <input name="name" class="form-control" required value="{{ old('name', $template->name) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Subject</label>
                            <input name="subject" class="form-control" required value="{{ old('subject', $template->subject) }}" data-preview-subject>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Body</label>
                            <textarea name="body" class="form-control" rows="9" required data-preview-source>{{ old('body', $template->body) }}</textarea>
                        </div>
                        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <input type="hidden" name="is_active" value="0">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="active_{{ $template->id }}" @checked($template->is_active)>
                                    <label class="form-check-label" for="active_{{ $template->id }}">Active</label>
                                </div>
                            </div>
                            <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Save template</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="tpl-preview-wrap">
                <div class="tpl-preview-label">Inbox preview</div>
                @include('partials.email-preview', [
                    'previewCompany' => $companyName,
                    'previewTypeLabel' => $typeLabel,
                    'previewBody' => $sampleBody,
                    'previewMeta' => $sampleMeta,
                    'previewAttachment' => in_array($template->document_type, ['quotation', 'proforma', 'invoice', 'receipt'], true)
                        ? strtoupper($template->document_type).'-sample.pdf'
                        : null,
                ])
            </div>
        </div>
    </div>
@endforeach
</div>
@endsection

@push('scripts')
<script>
(function () {
    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function fillSample(text) {
        return String(text)
            .replaceAll('@{{customer_name}}', 'Acme Engineering')
            .replaceAll('@{{document_number}}', 'DOC-2026-001')
            .replaceAll('@{{document_date}}', @json(now()->format('d-m-Y')))
            .replaceAll('@{{amount}}', '₹ 1,25,000.00')
            .replaceAll('@{{company_name}}', @json($companyName))
            .replaceAll('@{{document_subject}}', 'CNC Vertical Machining Centre')
            .replaceAll('@{{message}}', 'Sharing an update regarding your recent enquiry with our team.');
    }

    document.querySelectorAll('[data-template-card]').forEach(function (card) {
        const source = card.querySelector('[data-preview-source]');
        const body = card.querySelector('[data-preview-body]');
        if (!source || !body) return;

        const render = function () {
            const filled = fillSample(source.value);
            body.innerHTML = escapeHtml(filled).replace(/\n/g, '<br>');
        };

        source.addEventListener('input', render);
    });
})();
</script>
@endpush
