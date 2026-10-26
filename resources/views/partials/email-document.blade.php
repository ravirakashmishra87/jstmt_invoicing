@php
    $type = $type ?? null;
    $id = $id ?? null;
    $label = $label ?? 'Email';
    $class = $class ?? 'btn btn-outline-primary';
@endphp
@if($type && $id && auth()->user()->hasPermission('communication.create'))
    <form method="POST" action="{{ route('communication.send-document', ['type' => $type, 'id' => $id]) }}" class="d-inline" onsubmit="return confirm('Send this {{ $type }} to the client by email now?')">
        @csrf
        <button class="{{ $class }}" type="submit">
            <i class="bi bi-envelope"></i> {{ $label }}
        </button>
    </form>
@endif
