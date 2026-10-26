@extends('layouts.app')
@section('title', isset($invoice) ? 'Edit invoice' : 'New invoice')
@section('page', isset($invoice) ? 'Edit '.$invoice->invoice_number : 'New invoice')
@section('content')
@php
    $record = $invoice;
    $customerOptions = $customers->map(function ($customer) {
        $billing = $customer->addresses->first(fn ($address) => $address->type === 'billing' && $address->is_default)
            ?? $customer->addresses->firstWhere('type', 'billing')
            ?? $customer->addresses->first();
        return ['id' => $customer->id, 'state' => $billing->state_code ?? ''];
    })->values();
@endphp
<div class="card"><div class="card-body">
<form method="POST" action="{{ $record ? route('invoices.update', $record) : route('invoices.store') }}">
    @csrf
    @if($record) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Customer</label>
            <select name="customer_id" id="customer_id" class="form-select" required>
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $record?->customer_id ?? $prefillCustomer) === (string) $customer->id)>
                        {{ $customer->customer_code }} — {{ $customer->legal_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Invoice date</label>
            <input type="date" name="invoice_date" required class="form-control" value="{{ old('invoice_date', $record?->invoice_date?->toDateString() ?? now()->toDateString()) }}">
        </div>
        <div class="col-md-3">
            <label class="form-label">Place of supply</label>
            <select name="place_of_supply_code" id="place_of_supply_code" class="form-select" required>
                <option value="">Select state</option>
                @foreach($states as $state)
                    <option value="{{ $state->code }}" @selected(old('place_of_supply_code', $record?->place_of_supply_code ?? '') === $state->code)>{{ $state->code }} — {{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Payment term</label>
            <select name="payment_term_id" class="form-select">
                <option value="">—</option>
                @foreach($paymentTerms as $term)
                    <option value="{{ $term->id }}" @selected((string) old('payment_term_id', $record?->payment_term_id ?? '') === (string) $term->id)>{{ $term->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">Buyer order no.</label><input name="buyer_order_no" class="form-control" value="{{ old('buyer_order_no', $record?->buyer_order_no ?? '') }}"></div>
        <div class="col-md-2"><label class="form-label">Order date</label><input type="date" name="buyer_order_date" class="form-control" value="{{ old('buyer_order_date', $record?->buyer_order_date?->toDateString()) }}"></div>
        <div class="col-md-3"><label class="form-label">Destination</label><input name="destination" class="form-control" value="{{ old('destination', $record?->destination ?? '') }}"></div>
        <div class="col-md-2"><label class="form-label">Vehicle no.</label><input name="vehicle_no" class="form-control" value="{{ old('vehicle_no', $record?->vehicle_no ?? '') }}"></div>
        <div class="col-md-2"><label class="form-label">LR / RR</label><input name="lr_rr_no" class="form-control" value="{{ old('lr_rr_no', $record?->lr_rr_no ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Dispatched through</label><input name="dispatched_through" class="form-control" value="{{ old('dispatched_through', $record?->dispatched_through ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Terms of delivery</label><input name="terms_of_delivery" class="form-control" value="{{ old('terms_of_delivery', $record?->terms_of_delivery ?? '') }}"></div>
        <div class="col-md-4">
            <label class="form-label">Ship to</label>
            <div class="form-check mt-2">
                <input type="hidden" name="ship_same" value="0">
                <input class="form-check-input" type="checkbox" name="ship_same" value="1" id="ship_same" @checked(old('ship_same', '1') == '1' || old('ship_same') === true || old('ship_same') === 1)>
                <label class="form-check-label" for="ship_same">Same as billing address</label>
            </div>
        </div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" rows="2" class="form-control">{{ old('notes', $record?->notes ?? '') }}</textarea></div>
    </div>
    @include('partials.document-lines')
    <button class="btn btn-primary">Save draft</button>
    <a class="btn btn-outline-secondary" href="{{ route('invoices.index') }}">Cancel</a>
</form>
</div></div>
@push('scripts')
<script>
const customerStates = @json($customerOptions);
document.getElementById('customer_id')?.addEventListener('change', function () {
    const found = customerStates.find(function (row) { return String(row.id) === String(this.value); }, this);
    const pos = document.getElementById('place_of_supply_code');
    if (found && found.state && pos) pos.value = found.state;
});
</script>
@endpush
@endsection
