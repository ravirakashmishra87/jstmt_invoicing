@extends('layouts.app')
@section('title', 'Compose email')
@section('page', 'Compose email')
@section('subtitle', 'Communication')

@push('styles')
<style>
    .compose-shell {
        display: grid;
        grid-template-columns: minmax(0, 1.4fr) minmax(280px, .8fr);
        gap: 1rem;
    }
    @media (max-width: 991.98px) {
        .compose-shell { grid-template-columns: 1fr; }
    }
    .compose-card {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        overflow: hidden;
    }
    .compose-card-head {
        padding: .95rem 1.15rem;
        border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
    }
    .compose-card-head h2 {
        margin: 0;
        font-size: .98rem;
        font-weight: 700;
        color: var(--ink);
    }
    .compose-card-body { padding: 1.15rem; }
    .compose-card .form-label {
        font-size: .72rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
    }
    .compose-card .form-control,
    .compose-card .form-select {
        border-radius: 10px;
        min-height: 42px;
    }
    .compose-hint {
        border: 1px dashed #b7c9db;
        border-radius: 12px;
        background: #f8fafc;
        padding: 1rem;
        color: #475569;
        font-size: .92rem;
    }
    .compose-hint code {
        background: #e8eef5;
        color: var(--ink);
        padding: .1rem .35rem;
        border-radius: 6px;
        font-size: .8rem;
    }
    .attach-chip {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .45rem .75rem;
        border-radius: 999px;
        background: #eef5fb;
        color: var(--steel);
        font-weight: 700;
        font-size: .82rem;
        border: 1px solid #c5d8ea;
    }
    .compose-preview-wrap {
        background: linear-gradient(180deg, #dfeaf4 0%, #e7eef5 100%);
        padding: 1rem;
    }
    .email-preview-frame { max-width: 100%; }
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
        padding: 1.05rem 1.15rem .9rem;
        background: linear-gradient(135deg, #0f2744 0%, #16365c 55%, #1e4e79 100%);
        color: #fff;
    }
    .email-preview-mark {
        width: 40px;
        height: 40px;
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
        font-size: .95rem;
        line-height: 1.2;
    }
    .email-preview-tag {
        margin-top: .2rem;
        font-size: .65rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        color: #93a9c0;
    }
    .email-preview-accent { height: 4px; background: #2f6fa3; }
    .email-preview-body { padding: 1rem 1.1rem 1.05rem; background: #fff; }
    .email-preview-pill {
        display: inline-block;
        padding: .25rem .65rem;
        border-radius: 999px;
        background: #eef5fb;
        border: 1px solid #c5d7ea;
        color: #1e4e79;
        font-size: .65rem;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        margin-bottom: .8rem;
    }
    .email-preview-meta {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .45rem;
        background: #f4f8fc;
        border: 1px solid #d5dee8;
        border-radius: 12px;
        padding: .7rem;
        margin-bottom: .85rem;
    }
    .email-preview-meta-item span {
        display: block;
        font-size: .58rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .15rem;
    }
    .email-preview-meta-item strong {
        color: var(--ink);
        font-family: Barlow, sans-serif;
        font-size: .8rem;
        word-break: break-word;
    }
    .email-preview-copy {
        color: #334155;
        font-size: .88rem;
        line-height: 1.65;
        white-space: pre-wrap;
        min-height: 4rem;
    }
    .email-preview-attach {
        margin-top: .8rem;
        padding: .65rem .75rem;
        border-radius: 10px;
        border: 1px dashed #9db8d1;
        background: #f8fbfe;
        color: #16365c;
        font-size: .78rem;
    }
    .email-preview-attach.d-none,
    .email-preview-meta.d-none { display: none !important; }
    .email-preview-signoff {
        margin-top: .9rem;
        padding-top: .8rem;
        border-top: 1px solid #d5dee8;
        color: #64748b;
        font-size: .76rem;
    }
    .email-preview-foot {
        background: #0f2744;
        color: #93a9c0;
        text-align: center;
        font-size: .68rem;
        padding: .7rem .85rem;
        line-height: 1.45;
    }
</style>
@endpush

@section('content')
<div class="list-hero">
    <div class="kicker">Communication</div>
    <h1>Compose email</h1>
    <p>Select a document or write a general message. Templates and client email are filled automatically when available.</p>
</div>

@unless($smtpReady)
    <div class="alert alert-warning">SMTP is inactive. Configure Settings → Email / SMTP before sending.</div>
@endunless

<form method="POST" action="{{ route('communication.send') }}" id="compose-form">
    @csrf
    <div class="compose-shell">
        <div class="compose-card">
            <div class="compose-card-head">
                <h2><i class="bi bi-pencil-square"></i> Message</h2>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('communication.index') }}">Back</a>
            </div>
            <div class="compose-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Email type</label>
                        <select name="document_type" id="document_type" class="form-select" required>
                            @foreach(['general' => 'General email', 'quotation' => 'Quotation', 'invoice' => 'Invoice', 'receipt' => 'Receipt'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('document_type', $type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-8" id="document-wrap">
                        <label class="form-label">Document</label>
                        <select name="document_id" id="document_id" class="form-select">
                            <option value="">Select document</option>
                            @foreach($quotations as $row)
                                <option value="{{ $row->id }}" data-type="quotation" @selected((string) old('document_id', $documentId) === (string) $row->id && old('document_type', $type) === 'quotation')">
                                    {{ $row->quotation_number }} — {{ $row->customer_name }}
                                </option>
                            @endforeach
                            @foreach($invoices as $row)
                                <option value="{{ $row->id }}" data-type="invoice" @selected((string) old('document_id', $documentId) === (string) $row->id && old('document_type', $type) === 'invoice')">
                                    {{ $row->invoice_number }} — {{ $row->buyer_name }}
                                </option>
                            @endforeach
                            @foreach($receipts as $row)
                                <option value="{{ $row->id }}" data-type="receipt" @selected((string) old('document_id', $documentId) === (string) $row->id && old('document_type', $type) === 'receipt')">
                                    {{ $row->receipt_number }} — {{ $row->customer->legal_name ?? 'Customer' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">To</label>
                        <input type="email" name="to_email" id="to_email" class="form-control" required value="{{ old('to_email', $preview['to'] ?? '') }}" placeholder="client@company.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">CC (optional)</label>
                        <input type="email" name="cc_email" class="form-control" value="{{ old('cc_email') }}" placeholder="accounts@company.com">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Template</label>
                        <select id="template_id" class="form-select">
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" data-type="{{ $template->document_type }}" @selected(($preview['template_id'] ?? null) == $template->id)>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-primary w-100" id="apply-template"><i class="bi bi-magic"></i> Apply template</button>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Subject</label>
                        <input name="subject" id="subject" class="form-control" required value="{{ old('subject', $preview['subject'] ?? '') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Body</label>
                        <textarea name="body" id="body" class="form-control" rows="10" required>{{ old('body', $preview['body'] ?? '') }}</textarea>
                    </div>
                    <div class="col-12" id="attach-info" style="display:none;">
                        <span class="attach-chip"><i class="bi bi-paperclip"></i> PDF will be attached automatically</span>
                    </div>
                    <div class="col-12 d-flex flex-wrap gap-2">
                        <button class="btn btn-primary" type="submit" @disabled(! $smtpReady)>
                            <i class="bi bi-send-fill"></i> Send email
                        </button>
                        <a class="btn btn-outline-secondary" href="{{ route('communication.index') }}">Cancel</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="compose-card">
            <div class="compose-card-head">
                <h2><i class="bi bi-envelope-open"></i> Inbox preview</h2>
            </div>
            <div class="compose-preview-wrap">
                @include('partials.email-preview', [
                    'previewCompany' => $companyName ?? 'JST Machine Tools',
                    'previewTypeLabel' => $preview['type_label'] ?? 'Correspondence',
                    'previewBody' => $preview['body'] ?? '',
                    'previewMeta' => $preview['meta'] ?? [],
                    'previewAttachment' => in_array(old('document_type', $type), ['quotation', 'invoice', 'receipt'], true)
                        ? 'document.pdf'
                        : null,
                ])
                <div class="compose-hint mt-3 mb-0">
                    Recipients receive this branded HTML layout. PDF attachments are added automatically for quotations, invoices, and receipts.
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const typeEl = document.getElementById('document_type');
    const docEl = document.getElementById('document_id');
    const templateEl = document.getElementById('template_id');
    const attachInfo = document.getElementById('attach-info');
    const previewUrl = @json(route('communication.preview'));

    function syncDocumentOptions() {
        const type = typeEl.value;
        const showDoc = type !== 'general';
        document.getElementById('document-wrap').style.display = showDoc ? '' : 'none';
        attachInfo.style.display = showDoc ? '' : 'none';
        Array.from(docEl.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }
            const match = option.dataset.type === type;
            option.hidden = !match;
            if (!match && option.selected) option.selected = false;
        });
        Array.from(templateEl.options).forEach(function (option) {
            const match = option.dataset.type === type || option.dataset.type === 'general';
            option.hidden = !match;
        });
        const visibleTemplate = Array.from(templateEl.options).find(function (option) {
            return !option.hidden && option.dataset.type === type;
        }) || Array.from(templateEl.options).find(function (option) { return !option.hidden; });
        if (visibleTemplate) templateEl.value = visibleTemplate.value;
    }

    const bodyEl = document.getElementById('body');
    const previewBody = document.querySelector('[data-preview-body]');
    const previewType = document.querySelector('[data-preview-type]');
    const previewMeta = document.querySelector('[data-preview-meta]');
    const previewAttach = document.querySelector('[data-preview-attach]');
    const typeLabels = {
        general: 'Correspondence',
        quotation: 'Quotation',
        invoice: 'Tax Invoice',
        receipt: 'Payment Receipt',
    };

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderBodyPreview() {
        if (!previewBody || !bodyEl) return;
        previewBody.innerHTML = escapeHtml(bodyEl.value).replace(/\n/g, '<br>');
    }

    function renderMeta(meta) {
        if (!previewMeta) return;
        const entries = Object.entries(meta || {});
        if (!entries.length) {
            previewMeta.classList.add('d-none');
            previewMeta.innerHTML = '';
            return;
        }
        previewMeta.classList.remove('d-none');
        previewMeta.innerHTML = entries.map(function (pair) {
            return '<div class="email-preview-meta-item"><span>' + escapeHtml(pair[0]) + '</span><strong>' + escapeHtml(pair[1]) + '</strong></div>';
        }).join('');
    }

    function loadPreview() {
        const payload = {
            type: typeEl.value,
            document_id: docEl.value || null,
            template_id: templateEl.value || null,
        };
        fetch(previewUrl + '?' + new URLSearchParams(payload), {
            headers: { 'Accept': 'application/json' }
        }).then(function (response) { return response.json(); }).then(function (data) {
            if (data.to) document.getElementById('to_email').value = data.to;
            if (data.subject) document.getElementById('subject').value = data.subject;
            if (data.body) {
                bodyEl.value = data.body;
                renderBodyPreview();
            }
            if (previewType) previewType.textContent = data.type_label || typeLabels[typeEl.value] || 'Correspondence';
            renderMeta(data.meta || {});
            if (previewAttach) {
                previewAttach.classList.toggle('d-none', typeEl.value === 'general');
            }
        }).catch(function () {});
    }

    typeEl.addEventListener('change', function () {
        syncDocumentOptions();
        if (previewType) previewType.textContent = typeLabels[typeEl.value] || 'Correspondence';
        if (previewAttach) previewAttach.classList.toggle('d-none', typeEl.value === 'general');
        loadPreview();
    });
    docEl.addEventListener('change', loadPreview);
    document.getElementById('apply-template')?.addEventListener('click', loadPreview);
    bodyEl?.addEventListener('input', renderBodyPreview);

    syncDocumentOptions();
    renderBodyPreview();
})();
</script>
@endpush
