@php $row = $row ?? null; @endphp
@if($type === 'uoms')
    <div class="col-md-4">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}" placeholder="Numbers">
    </div>
    <div class="col-md-3">
        <label class="form-label">Code</label>
        <input name="code" class="form-control text-uppercase" required value="{{ old('code', $row?->code ?? '') }}" placeholder="NOS">
    </div>
@elseif($type === 'tax-rates')
    <div class="col-md-4">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}" placeholder="GST 18%">
    </div>
    <div class="col-md-3">
        <label class="form-label">Rate %</label>
        <input type="number" step="0.01" name="rate" class="form-control" required value="{{ old('rate', $row?->rate ?? '') }}" placeholder="18">
    </div>
@elseif($type === 'hsn')
    <div class="col-md-2">
        <label class="form-label">Code</label>
        <input name="code" class="form-control text-uppercase" required value="{{ old('code', $row?->code ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Description</label>
        <input name="description" class="form-control" value="{{ old('description', $row?->description ?? '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Type</label>
        <select name="type" class="form-select">
            <option value="HSN" @selected(old('type', $row?->type ?? 'HSN') === 'HSN')>HSN</option>
            <option value="SAC" @selected(old('type', $row?->type ?? '') === 'SAC')>SAC</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Default tax</label>
        <select name="tax_rate_id" class="form-select">
            <option value="">—</option>
            @foreach($taxRates as $rate)
                <option value="{{ $rate->id }}" @selected((string) old('tax_rate_id', $row?->tax_rate_id ?? '') === (string) $rate->id)>{{ $rate->name }}</option>
            @endforeach
        </select>
    </div>
@elseif($type === 'payment-terms')
    <div class="col-md-4">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Advance %</label>
        <input type="number" step="0.01" name="advance_percent" class="form-control" value="{{ old('advance_percent', $row?->advance_percent ?? 0) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Balance %</label>
        <input type="number" step="0.01" name="balance_percent" class="form-control" value="{{ old('balance_percent', $row?->balance_percent ?? 100) }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Due days</label>
        <input type="number" name="due_days" class="form-control" value="{{ old('due_days', $row?->due_days ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Milestone</label>
        <input name="milestone" class="form-control" value="{{ old('milestone', $row?->milestone ?? '') }}">
    </div>
    <div class="col-md-8">
        <label class="form-label">Description</label>
        <input name="description" class="form-control" value="{{ old('description', $row?->description ?? '') }}">
    </div>
@elseif($type === 'charge-types')
    <div class="col-md-3">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Code</label>
        <input name="code" class="form-control text-uppercase" required value="{{ old('code', $row?->code ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">HSN / SAC</label>
        <select name="hsn_sac_id" class="form-select">
            <option value="">—</option>
            @foreach($hsnCodes as $code)
                <option value="{{ $code->id }}" @selected((string) old('hsn_sac_id', $row?->hsn_sac_id ?? '') === (string) $code->id)>{{ $code->code }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Tax</label>
        <select name="tax_rate_id" class="form-select">
            <option value="">—</option>
            @foreach($taxRates as $rate)
                <option value="{{ $rate->id }}" @selected((string) old('tax_rate_id', $row?->tax_rate_id ?? '') === (string) $rate->id)>{{ $rate->rate }}%</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Taxable</label>
        <div class="form-check mt-2">
            <input type="hidden" name="is_taxable" value="0">
            <input class="form-check-input" type="checkbox" name="is_taxable" value="1" id="is_taxable" @checked(old('is_taxable', $row?->is_taxable ?? true))>
            <label class="form-check-label" for="is_taxable">Yes</label>
        </div>
    </div>
@elseif($type === 'transporters')
    <div class="col-md-4">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">GSTIN</label>
        <input name="gstin" maxlength="15" class="form-control text-uppercase" value="{{ old('gstin', $row?->gstin ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Transporter ID</label>
        <input name="transporter_id" class="form-control" value="{{ old('transporter_id', $row?->transporter_id ?? '') }}">
    </div>
    <div class="col-md-2">
        <label class="form-label">Phone</label>
        <input name="phone" class="form-control" value="{{ old('phone', $row?->phone ?? '') }}">
    </div>
@elseif($type === 'categories')
    <div class="col-md-4">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Parent</label>
        <select name="parent_id" class="form-select">
            <option value="">— Top level —</option>
            @foreach($categories as $category)
                @if(! $row || $category->id !== $row?->id)
                    <option value="{{ $category->id }}" @selected((string) old('parent_id', $row?->parent_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <label class="form-label">Sort</label>
        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $row?->sort_order ?? 0) }}">
    </div>
@elseif($type === 'brands' || $type === 'payment-modes')
    <div class="col-md-6">
        <label class="form-label">Name</label>
        <input name="name" class="form-control" required value="{{ old('name', $row?->name ?? '') }}">
    </div>
@endif
<div class="col-md-2">
    <label class="form-label">Status</label>
    <div class="form-check mt-2">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_master" @checked(in_array((string) old('is_active', ($row?->is_active ?? true) ? '1' : '0'), ['1'], true))>
        <label class="form-check-label" for="is_active_master">Active</label>
    </div>
</div>
