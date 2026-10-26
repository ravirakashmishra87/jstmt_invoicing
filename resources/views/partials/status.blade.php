@php
    $tone = [
        'draft' => 'secondary',
        'sent' => 'info',
        'under_negotiation' => 'warning',
        'accepted' => 'success',
        'rejected' => 'danger',
        'expired' => 'dark',
        'revised' => 'secondary',
        'issued' => 'primary',
        'partially_paid' => 'warning',
        'paid' => 'success',
        'cancelled' => 'danger',
        'posted' => 'success',
        'approved' => 'success',
        'in_stock' => 'success',
        'sold' => 'primary',
        'reserved' => 'warning',
        'returned' => 'info',
        'under_repair' => 'warning',
        'scrapped' => 'dark',
    ];
@endphp
<span class="badge text-bg-{{ $tone[$status] ?? 'secondary' }}">{{ \Illuminate\Support\Str::headline($status) }}</span>
