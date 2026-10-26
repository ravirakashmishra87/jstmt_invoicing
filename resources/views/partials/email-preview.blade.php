@php
    $previewCompany = $previewCompany ?? ($company->trade_name ?? $company->name ?? 'JST Machine Tools');
    $previewTypeLabel = $previewTypeLabel ?? 'Correspondence';
    $previewBody = $previewBody ?? '';
    $previewMeta = $previewMeta ?? [];
    $previewAttachment = $previewAttachment ?? null;
    $previewTagline = $previewTagline ?? 'Precision machine tools';
@endphp
<div class="email-preview-frame" data-email-preview>
    <div class="email-preview-shell">
        <div class="email-preview-masthead">
            <div class="email-preview-mark">JST</div>
            <div>
                <div class="email-preview-brand" data-preview-company>{{ $previewCompany }}</div>
                <div class="email-preview-tag">{{ $previewTagline }}</div>
            </div>
        </div>
        <div class="email-preview-accent"></div>
        <div class="email-preview-body">
            <div class="email-preview-pill" data-preview-type>{{ $previewTypeLabel }}</div>
            <div class="email-preview-meta @if(empty($previewMeta)) d-none @endif" data-preview-meta>
                @forelse($previewMeta as $label => $value)
                    <div class="email-preview-meta-item">
                        <span>{{ $label }}</span>
                        <strong>{{ $value }}</strong>
                    </div>
                @empty
                @endforelse
            </div>
            <div class="email-preview-copy" data-preview-body>{!! nl2br(e($previewBody)) !!}</div>
            <div class="email-preview-attach @if(! $previewAttachment) d-none @endif" data-preview-attach>
                <strong>PDF attached</strong>
                <span data-preview-attach-name>{{ $previewAttachment }}</span>
            </div>
            <div class="email-preview-signoff">
                This message was sent by <strong data-preview-company-foot>{{ $previewCompany }}</strong>.
            </div>
        </div>
        <div class="email-preview-foot">
            Confidential business correspondence.
        </div>
    </div>
</div>
