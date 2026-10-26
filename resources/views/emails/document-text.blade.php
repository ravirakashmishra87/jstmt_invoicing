{{ $companyName }}
{{ $typeLabel ?? 'Correspondence' }}
@if(!empty($meta))

@foreach($meta as $label => $value)
{{ $label }}: {{ $value }}
@endforeach
@endif

{{ $bodyText }}

@if(!empty($attachmentName))
Attachment: {{ $attachmentName }}
@endif

—
{{ $companyName }}
@if(!empty($companyContact))
{{ $companyContact }}
@endif
{{ $footerNote ?: 'Confidential business correspondence.' }}
