@php
    $viewUrl = $viewUrl ?? null;
    $editUrl = $editUrl ?? null;
    $deleteUrl = $deleteUrl ?? null;
    $deleteConfirm = $deleteConfirm ?? 'Delete this record?';
    $extra = $extra ?? null;
@endphp
<div class="icon-actions justify-content-end">
    @if($viewUrl)
        <a class="icon-btn" href="{{ $viewUrl }}" title="View" aria-label="View">
            <i class="bi bi-eye"></i>
        </a>
    @endif
    @if($editUrl)
        <a class="icon-btn" href="{{ $editUrl }}" title="Edit" aria-label="Edit">
            <i class="bi bi-pencil-square"></i>
        </a>
    @endif
    @if($extra)
        {!! $extra !!}
    @endif
    @if($deleteUrl)
        <form method="POST" action="{{ $deleteUrl }}" onsubmit="return confirm(@js($deleteConfirm))">
            @csrf
            @method('DELETE')
            <button class="icon-btn danger" type="submit" title="Delete" aria-label="Delete">
                <i class="bi bi-trash3"></i>
            </button>
        </form>
    @endif
</div>
