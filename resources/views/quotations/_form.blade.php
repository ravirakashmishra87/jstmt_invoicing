@php
    $record = $quotation;
    $customerOptions = $customers->map(function ($customer) {
        $billing = $customer->addresses->first(fn ($address) => $address->type === 'billing' && $address->is_default)
            ?? $customer->addresses->firstWhere('type', 'billing')
            ?? $customer->addresses->first();
        return ['id' => $customer->id, 'state' => $billing->state_code ?? ''];
    })->values();
@endphp
<form method="POST" action="{{ $record ? route('quotations.update', $record) : route('quotations.store') }}">
    @csrf
    @if($record) @method('PUT') @endif
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <label class="form-label">Customer</label>
            <select name="customer_id" id="customer_id" class="form-select" required>
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((string) old('customer_id', $record?->customer_id ?? request('customer_id')) === (string) $customer->id)>
                        {{ $customer->customer_code }} — {{ $customer->legal_name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Date</label>
            <input type="date" name="quotation_date" class="form-control" required value="{{ old('quotation_date', $record?->quotation_date?->toDateString() ?? now()->toDateString()) }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Valid until</label>
            <input type="date" name="valid_until" class="form-control" value="{{ old('valid_until', $record?->valid_until?->toDateString()) }}">
        </div>
        <div class="col-md-4">
            <label class="form-label">Place of supply</label>
            <select name="place_of_supply_code" id="place_of_supply_code" class="form-select" required>
                <option value="">Select state</option>
                @foreach($states as $state)
                    <option value="{{ $state->code }}" @selected(old('place_of_supply_code', $record?->place_of_supply_code ?? '') === $state->code)>{{ $state->code }} — {{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Subject</label>
            <input name="subject" class="form-control" value="{{ old('subject', $record?->subject ?? '') }}">
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
        <div class="col-md-3 d-flex align-items-end gap-3">
            <div class="form-check">
                <input type="hidden" name="show_bank_details" value="0">
                <input class="form-check-input" type="checkbox" name="show_bank_details" value="1" @checked(in_array((string) old('show_bank_details', ($record?->show_bank_details ?? true) ? '1' : '0'), ['1'], true))>
                <label class="form-check-label">Show bank</label>
            </div>
            <div class="form-check">
                <input type="hidden" name="show_images" value="0">
                <input class="form-check-input" type="checkbox" name="show_images" value="1" @checked(in_array((string) old('show_images', ($record?->show_images ?? true) ? '1' : '0'), ['1'], true))>
                <label class="form-check-label">Show images</label>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes', $record?->notes ?? '') }}</textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Terms</label>
            <textarea name="terms_content" class="form-control" rows="4">{{ old('terms_content', $record?->terms_content ?? $defaultTerms) }}</textarea>
        </div>
    </div>
    @include('partials.document-lines', ['allowAtActual' => true])
    <button class="btn btn-primary" type="submit">Save quotation</button>
    <a class="btn btn-outline-secondary" href="{{ route('quotations.index') }}">Cancel</a>
</form>
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
