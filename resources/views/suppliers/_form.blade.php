@php
    $supplier = $supplier ?? null;
    $address = $supplier?->addresses?->first();
    $active = old('is_active', $supplier ? ($supplier?->is_active ? '1' : '0') : '1');
@endphp
<form method="POST" action="{{ $supplier ? route('suppliers.update', $supplier) : route('suppliers.store') }}">
    @csrf
    @if($supplier) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">Code</label><input name="supplier_code" class="form-control" value="{{ old('supplier_code', $supplier?->supplier_code ?? '') }}" placeholder="Auto"></div>
        <div class="col-md-5"><label class="form-label">Legal name</label><input name="legal_name" required class="form-control" value="{{ old('legal_name', $supplier?->legal_name ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Trade name</label><input name="trade_name" class="form-control" value="{{ old('trade_name', $supplier?->trade_name ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">GSTIN</label><input name="gstin" maxlength="15" class="form-control" value="{{ old('gstin', $supplier?->gstin ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">PAN</label><input name="pan" maxlength="10" class="form-control" value="{{ old('pan', $supplier?->pan ?? '') }}"></div>
        <div class="col-md-3">
            <label class="form-label">GST type</label>
            <select name="gst_registration_type" class="form-select">
                @foreach(['regular', 'composition', 'unregistered', 'sez', 'export'] as $type)
                    <option value="{{ $type }}" @selected(old('gst_registration_type', $supplier?->gst_registration_type ?? 'regular') === $type)>{{ \Illuminate\Support\Str::headline($type) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
            <div class="form-check mb-2">
                <input type="hidden" name="is_active" value="0">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($active === '1' || $active === 1 || $active === true)>
                <label class="form-check-label">Active</label>
            </div>
        </div>
        <div class="col-md-4"><label class="form-label">Contact</label><input name="contact_person" class="form-control" value="{{ old('contact_person', $supplier?->contact_person ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="{{ old('mobile', $supplier?->mobile ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $supplier?->email ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Bank</label><input name="bank_name" class="form-control" value="{{ old('bank_name', $supplier?->bank_name ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Account</label><input name="bank_account" class="form-control" value="{{ old('bank_account', $supplier?->bank_account ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">IFSC</label><input name="bank_ifsc" class="form-control" value="{{ old('bank_ifsc', $supplier?->bank_ifsc ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label">Address line 1</label><input name="address_line1" class="form-control" value="{{ old('address_line1', $address?->address_line1 ?? '') }}"></div>
        <div class="col-md-6"><label class="form-label">Address line 2</label><input name="address_line2" class="form-control" value="{{ old('address_line2', $address?->address_line2 ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city', $address?->city ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">District</label><input name="district" class="form-control" value="{{ old('district', $address?->district ?? '') }}"></div>
        <div class="col-md-4">
            <label class="form-label">State</label>
            <select name="state_id" class="form-select">
                <option value="">—</option>
                @foreach($states as $state)
                    <option value="{{ $state->id }}" @selected((string) old('state_id', $address?->state_id ?? '') === (string) $state->id)>{{ $state->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">PIN</label><input name="pin" class="form-control" value="{{ old('pin', $address?->pin ?? '') }}"></div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control" rows="2">{{ old('notes', $supplier?->notes ?? '') }}</textarea></div>
    </div>
    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save supplier</button>
        <a class="btn btn-outline-secondary" href="{{ route('suppliers.index') }}">Cancel</a>
    </div>
</form>
