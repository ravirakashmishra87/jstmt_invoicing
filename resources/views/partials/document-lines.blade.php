@php
    $itemRows = array_values(old('items', $itemRows ?? []));
    $chargeRows = array_values(old('charges', $chargeRows ?? []));
    if ($itemRows === []) {
        $itemRows = [['product_id' => '', 'description' => '', 'quantity' => 1, 'uom' => '', 'rate' => '', 'discount_percent' => 0, 'tax_rate' => '']];
    }
    if ($chargeRows === []) {
        $chargeRows = [['charge_type_id' => '', 'name' => '', 'quantity' => 1, 'uom' => '', 'rate' => '', 'amount' => '', 'is_at_actual' => false]];
    }
    $allowAtActual = $allowAtActual ?? false;
    $chargeQtyUnit = $chargeQtyUnit ?? $allowAtActual;
    $chargesTitle = $chargesTitle ?? ($allowAtActual ? 'Accessories and charges' : 'Charges');
    $uoms = $uoms ?? collect();
@endphp

<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Line items</span>
        <button class="btn btn-sm btn-outline-primary" type="button" id="add-line">Add line</button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="line-items">
            <thead>
                <tr>
                    <th style="min-width: 220px;">Product</th>
                    <th style="min-width: 180px;">Description</th>
                    <th>HSN</th>
                    <th style="width: 90px;">Qty</th>
                    <th style="width: 110px;">Unit</th>
                    <th style="width: 120px;">Rate</th>
                    <th style="width: 90px;">Disc %</th>
                    <th style="width: 90px;">GST %</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($itemRows as $i => $row)
                    <tr>
                        <td>
                            <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm product-select">
                                <option value="">Manual line</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}"
                                        data-rate="{{ (float) $product->list_price }}"
                                        data-tax="{{ $product->taxRate->rate ?? '' }}"
                                        data-hsn="{{ $product->hsnSac->code ?? '' }}"
                                        data-uom="{{ $product->uom->code ?? '' }}"
                                        @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>
                                        {{ $product->sku }} — {{ $product->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="items[{{ $i }}][description]" class="form-control form-control-sm" value="{{ $row['description'] ?? '' }}"></td>
                        <td class="line-hsn small text-muted">—</td>
                        <td><input name="items[{{ $i }}][quantity]" class="form-control form-control-sm line-qty" value="{{ ($row['quantity'] ?? '') === '' ? '' : (float) $row['quantity'] }}"></td>
                        <td>
                            <select name="items[{{ $i }}][uom]" class="form-select form-select-sm line-uom">
                                <option value="">—</option>
                                @foreach($uoms as $uom)
                                    <option value="{{ $uom->code }}" @selected((string) ($row['uom'] ?? '') === (string) $uom->code)>{{ $uom->code }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="items[{{ $i }}][rate]" class="form-control form-control-sm line-rate" value="{{ ($row['rate'] ?? '') === '' ? '' : (float) $row['rate'] }}"></td>
                        <td><input name="items[{{ $i }}][discount_percent]" class="form-control form-control-sm line-disc" value="{{ (float) ($row['discount_percent'] ?? 0) }}"></td>
                        <td><input name="items[{{ $i }}][tax_rate]" class="form-control form-control-sm line-tax" value="{{ ($row['tax_rate'] ?? '') === '' ? '' : (float) $row['tax_rate'] }}"></td>
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line">×</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">{{ $chargesTitle }}</span>
        <button class="btn btn-sm btn-outline-primary" type="button" id="add-charge">Add line</button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0" id="charge-items">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Name</th>
                    @if($chargeQtyUnit)
                        <th style="width: 90px;">Qty</th>
                        <th style="width: 110px;">Unit</th>
                        <th style="width: 120px;">Rate</th>
                    @else
                        <th style="width: 140px;">Amount</th>
                    @endif
                    @if($allowAtActual)<th>At actual</th>@endif
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($chargeRows as $i => $row)
                    @php
                        $chargeQty = ($row['quantity'] ?? '') === '' ? 1 : (float) $row['quantity'];
                        $chargeRate = ($row['rate'] ?? '') === ''
                            ? (($row['amount'] ?? '') === '' || $chargeQty <= 0 ? '' : round(((float) $row['amount']) / $chargeQty, 4))
                            : (float) $row['rate'];
                    @endphp
                    <tr>
                        <td>
                            <select name="charges[{{ $i }}][charge_type_id]" class="form-select form-select-sm">
                                <option value="">—</option>
                                @foreach($chargeTypes as $type)
                                    <option value="{{ $type->id }}" @selected((string) ($row['charge_type_id'] ?? '') === (string) $type->id)>{{ $type->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input name="charges[{{ $i }}][name]" class="form-control form-control-sm" value="{{ $row['name'] ?? '' }}"></td>
                        @if($chargeQtyUnit)
                            <td>
                                <input name="charges[{{ $i }}][quantity]" class="form-control form-control-sm charge-qty" value="{{ $chargeQty }}">
                            </td>
                            <td>
                                <select name="charges[{{ $i }}][uom]" class="form-select form-select-sm charge-uom">
                                    <option value="">—</option>
                                    @foreach($uoms as $uom)
                                        <option value="{{ $uom->code }}" @selected((string) ($row['uom'] ?? '') === (string) $uom->code)>{{ $uom->code }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input name="charges[{{ $i }}][rate]" type="number" step="0.01" min="0" class="form-control form-control-sm charge-rate" value="{{ $chargeRate === '' ? '' : $chargeRate }}">
                            </td>
                        @else
                            <td><input name="charges[{{ $i }}][amount]" class="form-control form-control-sm" value="{{ ($row['amount'] ?? '') === '' ? '' : (float) $row['amount'] }}"></td>
                        @endif
                        @if($allowAtActual)
                            <td class="text-center">
                                <input type="hidden" name="charges[{{ $i }}][is_at_actual]" value="0">
                                <input type="checkbox" name="charges[{{ $i }}][is_at_actual]" value="1" @checked(!empty($row['is_at_actual']) && $row['is_at_actual'] !== '0')>
                            </td>
                        @endif
                        <td><button type="button" class="btn btn-sm btn-outline-danger remove-charge">×</button></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function applyProduct(select, overwrite) {
        const option = select.selectedOptions[0];
        const row = select.closest('tr');
        const hsn = row.querySelector('.line-hsn');
        if (hsn) hsn.textContent = option && option.dataset.hsn ? option.dataset.hsn : '—';
        if (!overwrite || !option || !option.value) return;
        const rate = row.querySelector('.line-rate');
        const tax = row.querySelector('.line-tax');
        const uom = row.querySelector('.line-uom');
        if (rate) rate.value = option.dataset.rate || '';
        if (tax) tax.value = option.dataset.tax || '';
        if (uom && option.dataset.uom) {
            uom.value = option.dataset.uom;
        }
    }
    document.querySelectorAll('.product-select').forEach(function (select) { applyProduct(select, false); });
    const lines = document.getElementById('line-items');
    lines?.addEventListener('change', function (event) {
        if (event.target.classList.contains('product-select')) applyProduct(event.target, true);
    });
    document.getElementById('add-line')?.addEventListener('click', function () {
        const body = lines.querySelector('tbody');
        const clone = body.lastElementChild.cloneNode(true);
        const stamp = Date.now();
        clone.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + stamp + ']');
        });
        clone.querySelectorAll('input').forEach(function (el) {
            if (el.classList.contains('line-qty')) el.value = '1';
            else if (el.classList.contains('line-disc')) el.value = '0';
            else el.value = '';
        });
        clone.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
        const hsn = clone.querySelector('.line-hsn');
        if (hsn) hsn.textContent = '—';
        body.appendChild(clone);
    });
    lines?.addEventListener('click', function (event) {
        if (!event.target.closest('.remove-line')) return;
        const rows = lines.querySelectorAll('tbody tr');
        if (rows.length > 1) event.target.closest('tr').remove();
    });
    const charges = document.getElementById('charge-items');
    document.getElementById('add-charge')?.addEventListener('click', function () {
        const body = charges.querySelector('tbody');
        const clone = body.lastElementChild.cloneNode(true);
        const stamp = Date.now();
        clone.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace(/charges\[\d+\]/, 'charges[' + stamp + ']');
        });
        clone.querySelectorAll('input').forEach(function (el) {
            if (el.type === 'checkbox') el.checked = false;
            else if (el.classList.contains('charge-qty')) el.value = '1';
            else if (el.type !== 'hidden') el.value = '';
        });
        clone.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
        body.appendChild(clone);
    });
    charges?.addEventListener('click', function (event) {
        if (!event.target.closest('.remove-charge')) return;
        const rows = charges.querySelectorAll('tbody tr');
        if (rows.length > 1) event.target.closest('tr').remove();
    });
})();
</script>
@endpush
