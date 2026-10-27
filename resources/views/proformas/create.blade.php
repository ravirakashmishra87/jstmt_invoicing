@extends('layouts.app')
@section('title', isset($proforma) ? 'Edit proforma' : 'New proforma')
@section('page', isset($proforma) ? 'Edit '.$proforma->proforma_number : 'New proforma')
@section('content')
@php
    $record = $proforma;
    $prefill = $prefill ?? [];
    $availableQuotations = $availableQuotations ?? collect();
    $selectedQuotationId = old('quotation_id', $selectedQuotationId ?? $record?->quotation_id);
    $customerOptions = $customers->map(function ($customer) {
        $billing = $customer->addresses->first(fn ($address) => $address->type === 'billing' && $address->is_default)
            ?? $customer->addresses->firstWhere('type', 'billing')
            ?? $customer->addresses->first();
        return ['id' => $customer->id, 'state' => $billing->state_code ?? ''];
    })->values();
@endphp
<div class="card"><div class="card-body">
<form method="POST" action="{{ $record ? route('proformas.update', $record) : route('proformas.store') }}">
    @csrf
    @if($record) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Quotation number</label>
            @if($record)
                <input type="hidden" name="quotation_id" value="{{ $record->quotation_id }}">
                <input type="text" class="form-control" value="{{ $record->quotation?->quotation_number ?? '—' }}" readonly>
            @else
                <select name="quotation_id" id="quotation_id" class="form-select @error('quotation_id') is-invalid @enderror" required>
                    <option value="">Select quotation</option>
                    @foreach($availableQuotations as $quotation)
                        <option value="{{ $quotation->id }}"
                            data-customer="{{ $quotation->customer_id }}"
                            @selected((string) $selectedQuotationId === (string) $quotation->id)>
                            {{ $quotation->quotation_number }} — {{ $quotation->customer_name }}
                        </option>
                    @endforeach
                </select>
                @error('quotation_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Selecting a quotation loads its items. Quotations that already have a PI are hidden.</div>
            @endif
        </div>
        <div class="col-md-4">
            <label class="form-label">Customer</label>
            @php
                $selectedCustomerId = old('customer_id', $record?->customer_id ?? $prefillCustomer);
                $selectedCustomer = $customers->firstWhere('id', (int) $selectedCustomerId);
            @endphp
            @if($selectedQuotationId && $selectedCustomer)
                <input type="hidden" name="customer_id" id="customer_id" value="{{ $selectedCustomer->id }}">
                <input type="text" class="form-control" readonly value="{{ $selectedCustomer->customer_code }} — {{ $selectedCustomer->legal_name }}">
                <div class="form-text">Taken from the selected quotation.</div>
            @else
                <select name="customer_id" id="customer_id" class="form-select" required>
                    <option value="">Select quotation first</option>
                </select>
            @endif
        </div>
        <div class="col-md-2">
            <label class="form-label">Proforma date</label>
            <input type="date" name="proforma_date" required class="form-control" value="{{ old('proforma_date', $record?->proforma_date?->toDateString() ?? now()->toDateString()) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Valid until</label>
            <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $record?->valid_until?->toDateString() ?? ($prefill['valid_until'] ?? null)) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Place of supply</label>
            <select name="place_of_supply_code" id="place_of_supply_code" class="form-select" required>
                <option value="">Select state</option>
                @foreach($states as $state)
                    <option value="{{ $state->code }}" @selected(old('place_of_supply_code', $record?->place_of_supply_code ?? ($prefill['place_of_supply_code'] ?? '')) === $state->code)>{{ $state->code }} — {{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Payment term</label>
            <select name="payment_term_id" class="form-select">
                <option value="">—</option>
                @foreach($paymentTerms as $term)
                    <option value="{{ $term->id }}" @selected((string) old('payment_term_id', $record?->payment_term_id ?? ($prefill['payment_term_id'] ?? '')) === (string) $term->id)>{{ $term->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Buyer order no.</label><input name="buyer_order_no" class="form-control" value="{{ old('buyer_order_no', $record?->buyer_order_no ?? '') }}"></div>
        <div class="col-md-2"><label class="form-label">Order date</label><input type="date" name="buyer_order_date" class="form-control" value="{{ old('buyer_order_date', $record?->buyer_order_date?->toDateString()) }}"></div>
        <div class="col-md-4"><label class="form-label">Destination</label><input name="destination" class="form-control" value="{{ old('destination', $record?->destination ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Terms of delivery</label><input name="terms_of_delivery" class="form-control" value="{{ old('terms_of_delivery', $record?->terms_of_delivery ?? '') }}"></div>
        <div class="col-md-4">
            <label class="form-label">Ship to</label>
            <div class="form-check mt-2">
                <input type="hidden" name="ship_same" value="0">
                <input class="form-check-input" type="checkbox" name="ship_same" value="1" id="ship_same" @checked(old('ship_same', '1') == '1' || old('ship_same') === true || old('ship_same') === 1)>
                <label class="form-check-label" for="ship_same">Same as billing address</label>
            </div>
        </div>
        <input type="hidden" name="reference_no" value="{{ old('reference_no', $record?->reference_no ?? ($prefill['reference_no'] ?? '')) }}">
        <input type="hidden" name="reference_date" value="{{ old('reference_date', $record?->reference_date?->toDateString() ?? ($prefill['reference_date'] ?? '')) }}">
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $record?->notes ?? ($prefill['notes'] ?? '')) }}</textarea></div>
    </div>
    @include('partials.document-lines')
    <button class="btn btn-primary" @disabled(! $record && ! $selectedQuotationId)>Save proforma</button>
    <a class="btn btn-outline-secondary" href="{{ route('proformas.index') }}">Cancel</a>
</form>
</div></div>
@push('scripts')
<script>
const customerStates = @json($customerOptions);
const createUrl = @json(route('proformas.create'));
document.getElementById('customer_id')?.addEventListener('change', function () {
    const found = customerStates.find(function (row) { return String(row.id) === String(this.value); }, this);
    const pos = document.getElementById('place_of_supply_code');
    if (found && found.state && pos) pos.value = found.state;
});
document.getElementById('quotation_id')?.addEventListener('change', function () {
    const id = this.value;
    const url = new URL(createUrl, window.location.origin);
    if (id) {
        url.searchParams.set('quotation_id', id);
    }
    window.location = url.toString();
});
</script>
@endpush
@endsection
