@php
    $billing = isset($customer) ? $customer->addresses->firstWhere('type', 'billing') : null;
    $shipping = isset($customer) ? $customer->addresses->firstWhere('type', 'shipping') : null;
    $addresses = old('addresses', [
        [
            'type' => 'billing', 'label' => 'Billing',
            'address_line1' => $billing->address_line1 ?? '', 'address_line2' => $billing->address_line2 ?? '',
            'city' => $billing->city ?? '', 'district' => $billing->district ?? '',
            'state_id' => $billing->state_id ?? '', 'pin' => $billing->pin ?? '',
            'country' => $billing->country ?? 'India', 'is_default' => true,
        ],
        [
            'type' => 'shipping', 'label' => 'Shipping',
            'address_line1' => $shipping->address_line1 ?? '', 'address_line2' => $shipping->address_line2 ?? '',
            'city' => $shipping->city ?? '', 'district' => $shipping->district ?? '',
            'state_id' => $shipping->state_id ?? '', 'pin' => $shipping->pin ?? '',
            'country' => $shipping->country ?? 'India', 'is_default' => true,
        ],
    ]);
    $active = old('is_active', isset($customer) ? ($customer->is_active ? '1' : '0') : '1');
    $isEdit = (bool) ($customer->exists ?? false);
@endphp

@push('styles')
<style>
    .form-panel {
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 14px;
        box-shadow: 0 8px 20px rgba(15, 39, 68, .04);
        margin-bottom: 1rem;
        overflow: hidden;
    }
    .form-panel-head {
        padding: .95rem 1.2rem;
        border-bottom: 1px solid var(--line);
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }
    .form-panel-head h2 {
        margin: 0;
        font-size: .98rem;
        font-weight: 700;
        color: var(--ink);
        display: flex;
        align-items: center;
        gap: .5rem;
    }
    .form-panel-head .hint {
        color: #64748b;
        font-size: .8rem;
    }
    .form-panel-body { padding: 1.2rem; }
    .form-panel .form-label {
        font-size: .78rem;
        letter-spacing: .04em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .35rem;
    }
    .form-panel .form-control,
    .form-panel .form-select {
        border-radius: 10px;
        border-color: #d5dee8;
        padding: .6rem .8rem;
        min-height: 42px;
    }
    .form-panel .form-control:focus,
    .form-panel .form-select:focus {
        border-color: #2f6fa3;
        box-shadow: 0 0 0 .2rem rgba(47, 111, 163, .15);
    }
    .req::after {
        content: " *";
        color: #dc2626;
        font-weight: 700;
    }
    .addr-block {
        border: 1px solid var(--line);
        border-radius: 12px;
        padding: 1rem;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 60%);
        height: 100%;
    }
    .addr-block .addr-title {
        font-size: .78rem;
        letter-spacing: .1em;
        text-transform: uppercase;
        font-weight: 700;
        color: var(--steel);
        margin-bottom: .85rem;
        display: flex;
        align-items: center;
        gap: .45rem;
    }
    .form-footer {
        position: sticky;
        bottom: 0;
        z-index: 10;
        background: rgba(231, 238, 245, .92);
        backdrop-filter: blur(8px);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: .85rem 1rem;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: .75rem;
        margin-top: .25rem;
        box-shadow: 0 -8px 24px rgba(15, 39, 68, .06);
    }
    .form-footer .meta {
        color: #64748b;
        font-size: .88rem;
    }
    .form-footer .btn {
        border-radius: 10px;
        font-weight: 600;
        min-width: 120px;
    }
    .copy-ship {
        font-size: .82rem;
        font-weight: 600;
    }
</style>
@endpush

<form method="POST" action="{{ $isEdit ? route('customers.update', $customer) : route('customers.store') }}" id="customerForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="form-panel">
        <div class="form-panel-head">
            <h2><i class="bi bi-building"></i> Identity &amp; tax</h2>
            <span class="hint">Legal name appears on GST documents</span>
        </div>
        <div class="form-panel-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Customer code</label>
                    <input name="customer_code" class="form-control @error('customer_code') is-invalid @enderror" value="{{ old('customer_code', $customer->customer_code ?? '') }}" placeholder="Auto-generated">
                    @error('customer_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label class="form-label req">Legal name</label>
                    <input name="legal_name" class="form-control @error('legal_name') is-invalid @enderror" required value="{{ old('legal_name', $customer->legal_name ?? '') }}" placeholder="Registered company name">
                    @error('legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Trade name</label>
                    <input name="trade_name" class="form-control" value="{{ old('trade_name', $customer->trade_name ?? '') }}" placeholder="Optional brand / trade name">
                </div>
                <div class="col-md-3">
                    <label class="form-label">GSTIN</label>
                    <input name="gstin" class="form-control text-uppercase @error('gstin') is-invalid @enderror" maxlength="15" value="{{ old('gstin', $customer->gstin ?? '') }}" placeholder="15-character GSTIN">
                    @error('gstin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">PAN</label>
                    <input name="pan" class="form-control text-uppercase" maxlength="10" value="{{ old('pan', $customer->pan ?? '') }}" placeholder="ABCDE1234F">
                </div>
                <div class="col-md-3">
                    <label class="form-label">GST registration</label>
                    <select name="gst_registration_type" class="form-select">
                        @foreach(['regular', 'composition', 'unregistered', 'sez', 'export'] as $type)
                            <option value="{{ $type }}" @selected(old('gst_registration_type', $customer->gst_registration_type ?? 'regular') === $type)>{{ \Illuminate\Support\Str::headline($type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Default payment term</label>
                    <select name="payment_term_id" class="form-select">
                        <option value="">Select term</option>
                        @foreach($paymentTerms as $term)
                            <option value="{{ $term->id }}" @selected((string) old('payment_term_id', $customer->payment_term_id ?? '') === (string) $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="form-panel">
        <div class="form-panel-head">
            <h2><i class="bi bi-person-lines-fill"></i> Contact &amp; credit</h2>
            <span class="hint">Used for follow-ups and credit control</span>
        </div>
        <div class="form-panel-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Contact person</label>
                    <input name="contact_person" class="form-control" value="{{ old('contact_person', $customer->contact_person ?? '') }}" placeholder="Purchase / accounts contact">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Mobile</label>
                    <input name="mobile" class="form-control" value="{{ old('mobile', $customer->mobile ?? '') }}" placeholder="+91 …">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $customer->email ?? '') }}" placeholder="accounts@company.com">
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label class="form-label">Credit limit (₹)</label>
                    <input type="number" step="0.01" name="credit_limit" class="form-control" value="{{ old('credit_limit', $customer->credit_limit ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Opening balance (₹)</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', $customer->opening_balance ?? 0) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Salesperson</label>
                    <select name="salesperson_id" class="form-select">
                        <option value="">Unassigned</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('salesperson_id', $customer->salesperson_id ?? '') === (string) $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">Status</label>
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked($active === '1' || $active === 1 || $active === true)>
                    <label class="form-check-label ms-1" for="is_active">Active customer</label>
                </div>
                <div class="col-12">
                    <label class="form-label">Internal notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Delivery preferences, credit remarks, special instructions…">{{ old('notes', $customer->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="form-panel">
        <div class="form-panel-head">
            <h2><i class="bi bi-geo-alt"></i> Addresses</h2>
            <button type="button" class="btn btn-sm btn-outline-primary copy-ship" id="copyBillingToShipping">
                <i class="bi bi-copy"></i> Copy billing → shipping
            </button>
        </div>
        <div class="form-panel-body">
            <div class="row g-3">
                @foreach($addresses as $i => $address)
                    <div class="col-lg-6">
                        <div class="addr-block" data-address-index="{{ $i }}">
                            <div class="addr-title">
                                <i class="bi bi-{{ ($address['type'] ?? '') === 'shipping' ? 'truck' : 'building' }}"></i>
                                {{ \Illuminate\Support\Str::headline($address['type'] ?? 'Address') }} address
                            </div>
                            <input type="hidden" name="addresses[{{ $i }}][type]" value="{{ $address['type'] }}">
                            <input type="hidden" name="addresses[{{ $i }}][label]" value="{{ $address['label'] ?? \Illuminate\Support\Str::headline($address['type'] ?? 'Address') }}">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Address line 1</label>
                                    <input name="addresses[{{ $i }}][address_line1]" class="form-control address-field" data-field="address_line1" value="{{ $address['address_line1'] ?? '' }}" placeholder="Building / street">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Address line 2</label>
                                    <input name="addresses[{{ $i }}][address_line2]" class="form-control address-field" data-field="address_line2" value="{{ $address['address_line2'] ?? '' }}" placeholder="Area / landmark">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input name="addresses[{{ $i }}][city]" class="form-control address-field" data-field="city" value="{{ $address['city'] ?? '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">District</label>
                                    <input name="addresses[{{ $i }}][district]" class="form-control address-field" data-field="district" value="{{ $address['district'] ?? '' }}">
                                </div>
                                <div class="col-md-7">
                                    <label class="form-label">State</label>
                                    <select name="addresses[{{ $i }}][state_id]" class="form-select address-field" data-field="state_id">
                                        <option value="">Select state</option>
                                        @foreach($states as $state)
                                            <option value="{{ $state->id }}" @selected((string) ($address['state_id'] ?? '') === (string) $state->id)>{{ $state->name }} ({{ $state->code }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">PIN</label>
                                    <input name="addresses[{{ $i }}][pin]" class="form-control address-field" data-field="pin" value="{{ $address['pin'] ?? '' }}" placeholder="6 digits">
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input type="hidden" name="addresses[{{ $i }}][is_default]" value="0">
                                        <input class="form-check-input address-default" type="checkbox" name="addresses[{{ $i }}][is_default]" value="1" id="addr_default_{{ $i }}" @checked(! empty($address['is_default']) && $address['is_default'] !== '0')>
                                        <label class="form-check-label" for="addr_default_{{ $i }}">Default {{ $address['type'] ?? '' }} address</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="form-footer">
        <div class="meta">
            @if($isEdit)
                Saving updates for <strong>{{ $customer->customer_code }}</strong>
            @else
                Code will auto-generate if left blank
            @endif
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="{{ $isEdit ? route('customers.show', $customer) : route('customers.index') }}">Cancel</a>
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-check2-circle"></i>
                {{ $isEdit ? 'Save changes' : 'Create customer' }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.getElementById('copyBillingToShipping')?.addEventListener('click', function () {
    const billing = document.querySelector('[data-address-index="0"]');
    const shipping = document.querySelector('[data-address-index="1"]');
    if (!billing || !shipping) return;

    billing.querySelectorAll('.address-field').forEach((field) => {
        const key = field.dataset.field;
        const target = shipping.querySelector('.address-field[data-field="' + key + '"]');
        if (!target) return;
        target.value = field.value;
    });

    const billingDefault = billing.querySelector('.address-default');
    const shippingDefault = shipping.querySelector('.address-default');
    if (billingDefault && shippingDefault) {
        shippingDefault.checked = billingDefault.checked;
    }
});
</script>
@endpush
