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

@push('styles')
<style>
    .line-block {
        border: 1px solid var(--line, #d5dee8);
        border-radius: 12px;
        background: #fff;
        margin-bottom: .75rem;
        overflow: hidden;
    }
    .line-block-main {
        display: grid;
        grid-template-columns: minmax(160px, 1.1fr) minmax(180px, 1.3fr) 70px 80px 100px 110px 80px 80px 40px;
        gap: .55rem;
        align-items: start;
        padding: .85rem .9rem;
    }
    @media (max-width: 1199.98px) {
        .line-block-main {
            grid-template-columns: minmax(140px, 1fr) minmax(160px, 1.2fr) 1fr 1fr;
        }
    }
    @media (max-width: 767.98px) {
        .line-block-main {
            grid-template-columns: 1fr;
        }
    }
    .line-block label {
        display: block;
        font-size: .68rem;
        letter-spacing: .06em;
        text-transform: uppercase;
        font-weight: 700;
        color: #64748b;
        margin-bottom: .25rem;
    }
    .line-block .form-control,
    .line-block .form-select {
        border-radius: 8px;
        min-height: 38px;
    }
    .line-description {
        min-height: 72px;
        resize: vertical;
        line-height: 1.45;
    }
    .line-hsn-value {
        min-height: 38px;
        display: flex;
        align-items: center;
        color: #64748b;
        font-size: .9rem;
    }
    .line-remove {
        padding-top: 1.35rem;
    }
</style>
@endpush

<div class="card mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Line items</span>
        <button class="btn btn-sm btn-outline-primary" type="button" id="add-line">Add line</button>
    </div>
    <div class="card-body" id="line-items">
        @foreach($itemRows as $i => $row)
            @php
                $descriptionValue = \App\Support\RichText::plain($row['description'] ?? '');
            @endphp
            <div class="line-block" data-line-block>
                <div class="line-block-main">
                    <div>
                        <label>Product</label>
                        <select name="items[{{ $i }}][product_id]" class="form-select form-select-sm product-select">
                            <option value="">Manual line</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    data-rate="{{ (float) $product->list_price }}"
                                    data-tax="{{ $product->taxRate->rate ?? '' }}"
                                    data-hsn="{{ $product->hsnSac->code ?? '' }}"
                                    data-uom="{{ $product->uom->code ?? '' }}"
                                    data-description="{{ e(\App\Support\RichText::plain($product->description ?? '')) }}"
                                    @selected((string) ($row['product_id'] ?? '') === (string) $product->id)>
                                    {{ $product->sku }} — {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Description</label>
                        <textarea name="items[{{ $i }}][description]" class="form-control form-control-sm line-description" rows="3" placeholder="Enter detailed description…">{{ $descriptionValue }}</textarea>
                    </div>
                    <div>
                        <label>HSN</label>
                        <div class="line-hsn-value line-hsn">—</div>
                    </div>
                    <div>
                        <label>Qty</label>
                        <input name="items[{{ $i }}][quantity]" class="form-control form-control-sm line-qty" value="{{ ($row['quantity'] ?? '') === '' ? '' : (float) $row['quantity'] }}">
                    </div>
                    <div>
                        <label>Unit</label>
                        <select name="items[{{ $i }}][uom]" class="form-select form-select-sm line-uom">
                            <option value="">—</option>
                            @foreach($uoms as $uom)
                                <option value="{{ $uom->code }}" @selected((string) ($row['uom'] ?? '') === (string) $uom->code)>{{ $uom->code }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Rate</label>
                        <input name="items[{{ $i }}][rate]" class="form-control form-control-sm line-rate" value="{{ ($row['rate'] ?? '') === '' ? '' : (float) $row['rate'] }}">
                    </div>
                    <div>
                        <label>Disc %</label>
                        <input name="items[{{ $i }}][discount_percent]" class="form-control form-control-sm line-disc" value="{{ (float) ($row['discount_percent'] ?? 0) }}">
                    </div>
                    <div>
                        <label>GST %</label>
                        <input name="items[{{ $i }}][tax_rate]" class="form-control form-control-sm line-tax" value="{{ ($row['tax_rate'] ?? '') === '' ? '' : (float) $row['tax_rate'] }}">
                    </div>
                    <div class="line-remove">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-line" title="Remove line">×</button>
                    </div>
                </div>
            </div>
        @endforeach
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
        const block = select.closest('[data-line-block]');
        const hsn = block.querySelector('.line-hsn');
        if (hsn) hsn.textContent = option && option.dataset.hsn ? option.dataset.hsn : '—';
        if (!overwrite || !option || !option.value) return;
        const rate = block.querySelector('.line-rate');
        const tax = block.querySelector('.line-tax');
        const uom = block.querySelector('.line-uom');
        const description = block.querySelector('.line-description');
        if (rate) rate.value = option.dataset.rate || '';
        if (tax) tax.value = option.dataset.tax || '';
        if (uom && option.dataset.uom) uom.value = option.dataset.uom;
        if (description && !description.value.trim() && option.dataset.description) {
            description.value = option.dataset.description;
        }
    }

    document.querySelectorAll('[data-line-block]').forEach(function (block) {
        const select = block.querySelector('.product-select');
        if (select) applyProduct(select, false);
    });

    const lines = document.getElementById('line-items');
    lines?.addEventListener('change', function (event) {
        if (event.target.classList.contains('product-select')) applyProduct(event.target, true);
    });

    document.getElementById('add-line')?.addEventListener('click', function () {
        const first = lines.querySelector('[data-line-block]');
        if (!first) return;
        const clone = first.cloneNode(true);
        const stamp = Date.now();
        clone.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace(/items\[[^\]]+\]/, 'items[' + stamp + ']');
        });
        clone.querySelectorAll('input, textarea').forEach(function (el) {
            if (el.classList.contains('line-qty')) el.value = '1';
            else if (el.classList.contains('line-disc')) el.value = '0';
            else el.value = '';
        });
        clone.querySelectorAll('select').forEach(function (el) { el.selectedIndex = 0; });
        const hsn = clone.querySelector('.line-hsn');
        if (hsn) hsn.textContent = '—';
        lines.appendChild(clone);
    });

    lines?.addEventListener('click', function (event) {
        if (!event.target.closest('.remove-line')) return;
        const blocks = lines.querySelectorAll('[data-line-block]');
        if (blocks.length > 1) event.target.closest('[data-line-block]').remove();
    });

    const charges = document.getElementById('charge-items');
    document.getElementById('add-charge')?.addEventListener('click', function () {
        const body = charges.querySelector('tbody');
        const clone = body.lastElementChild.cloneNode(true);
        const stamp = Date.now();
        clone.querySelectorAll('[name]').forEach(function (el) {
            el.name = el.name.replace(/charges\[\d+]/, 'charges[' + stamp + ']');
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
