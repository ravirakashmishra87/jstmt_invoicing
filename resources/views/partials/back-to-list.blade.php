@php
    $label = $label ?? 'Back to list';
    $url = $url ?? '#';
    $class = $class ?? 'btn btn-outline-secondary';
@endphp
<a href="{{ $url }}" class="{{ $class }}">
    <i class="bi bi-arrow-left"></i> {{ $label }}
</a>
