@php
    $product = $product ?? null;
    $active = old('track_serial', $product?->track_serial ? '1' : '0');
    $enabled = old('is_active', $product ? ($product?->is_active ? '1' : '0') : '1');
    $groups = old('spec_groups');
    if ($groups === null) {
        $groups = $product
            ? $product->specGroups->map(fn ($group) => [
                'name' => $group->name,
                'items' => $group->items->map(fn ($item) => ['label' => $item->label, 'value' => $item->value])->all() ?: [['label' => '', 'value' => '']],
            ])->all()
            : [['name' => '', 'items' => [['label' => '', 'value' => '']]]];
    }
    $groups = array_values($groups);
    if ($groups === []) {
        $groups = [['name' => '', 'items' => [['label' => '', 'value' => '']]]];
    }
    $components = old('components');
    if ($components === null) {
        $components = $product
            ? $product->components->map(fn ($row) => ['component_name' => $row->component_name, 'brand_name' => $row->brand_name])->all()
            : [['component_name' => '', 'brand_name' => '']];
    }
    $components = array_values($components);
    if ($components === []) {
        $components = [['component_name' => '', 'brand_name' => '']];
    }
@endphp
<form method="POST" action="{{ $product ? route('products.update', $product) : route('products.store') }}" enctype="multipart/form-data">
    @csrf
    @if($product) @method('PUT') @endif
    <div class="row g-3">
        <div class="col-md-3"><label class="form-label">SKU</label><input name="sku" required class="form-control" value="{{ old('sku', $product?->sku ?? '') }}"></div>
        <div class="col-md-5"><label class="form-label">Name</label><input name="name" required class="form-control" value="{{ old('name', $product?->name ?? '') }}"></div>
        <div class="col-md-4"><label class="form-label">Model number</label><input name="model_number" class="form-control" value="{{ old('model_number', $product?->model_number ?? '') }}"></div>
        <div class="col-12">
            <label class="form-label">Product image</label>
            <div class="d-flex flex-wrap align-items-start gap-3">
                @if($product?->primaryImageUrl())
                    <div class="text-center">
                        <img src="{{ $product->primaryImageUrl() }}" alt="{{ $product->name }}" class="rounded border" style="width: 140px; height: 140px; object-fit: cover;">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="remove_image">
                            <label class="form-check-label" for="remove_image">Remove current image</label>
                        </div>
                    </div>
                @endif
                <div class="flex-grow-1" style="min-width: 220px;">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="form-control @error('image') is-invalid @enderror">
                    <div class="form-text">JPG, PNG or WebP · max 5 MB. File is stored on disk; only the path is saved in the database. Used as the primary photo on quotations.</div>
                    @error('image')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <label class="form-label">Type</label>
            <select name="product_type" class="form-select">
                @foreach(['goods' => 'Goods', 'service' => 'Service'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('product_type', $product?->product_type ?? 'goods') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Goods type</label>
            <select name="goods_type" class="form-select">
                <option value="">—</option>
                @foreach(['machine', 'spare', 'consumable', 'accessory'] as $value)
                    <option value="{{ $value }}" @selected(old('goods_type', $product?->goods_type ?? '') === $value)>{{ \Illuminate\Support\Str::headline($value) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-select">
                <option value="">—</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('category_id', $product?->category_id ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Brand</label>
            <select name="brand_id" class="form-select">
                <option value="">—</option>
                @foreach($brands as $brand)
                    <option value="{{ $brand->id }}" @selected((string) old('brand_id', $product?->brand_id ?? '') === (string) $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">HSN / SAC</label>
            <select name="hsn_sac_id" class="form-select">
                <option value="">—</option>
                @foreach($hsnCodes as $code)
                    <option value="{{ $code->id }}" @selected((string) old('hsn_sac_id', $product?->hsn_sac_id ?? '') === (string) $code->id)>{{ $code->code }} — {{ $code->description }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">Tax rate</label>
            <select name="tax_rate_id" class="form-select">
                <option value="">—</option>
                @foreach($taxRates as $rate)
                    <option value="{{ $rate->id }}" @selected((string) old('tax_rate_id', $product?->tax_rate_id ?? '') === (string) $rate->id)>{{ $rate->name }} ({{ \App\Helpers\IndianFormat::number($rate->rate, 0) }}%)</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">UoM</label>
            <select name="uom_id" class="form-select">
                <option value="">—</option>
                @foreach($uoms as $uom)
                    <option value="{{ $uom->id }}" @selected((string) old('uom_id', $product?->uom_id ?? '') === (string) $uom->id)>{{ $uom->code }} — {{ $uom->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><label class="form-label">List price</label><input type="number" step="0.01" name="list_price" required class="form-control" value="{{ old('list_price', $product?->list_price ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Purchase price</label><input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price', $product?->purchase_price ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Min selling price</label><input type="number" step="0.01" name="min_selling_price" class="form-control" value="{{ old('min_selling_price', $product?->min_selling_price ?? '') }}"></div>
        <div class="col-md-3"><label class="form-label">Reorder level</label><input type="number" step="0.01" name="reorder_level" class="form-control" value="{{ old('reorder_level', $product?->reorder_level ?? 0) }}"></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description', $product?->description ?? '') }}</textarea></div>
        <div class="col-md-6">
            <input type="hidden" name="track_serial" value="0">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="track_serial" value="1" @checked($active === '1' || $active === 1 || $active === true)>
                <label class="form-check-label">Track serial numbers</label>
            </div>
        </div>
        <div class="col-md-6">
            <input type="hidden" name="is_active" value="0">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($enabled === '1' || $enabled === 1 || $enabled === true)>
                <label class="form-check-label">Active</label>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h2 class="h6 mb-0">Specifications</h2>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-spec-group">
            <i class="bi bi-plus-lg"></i> Add group
        </button>
    </div>
    <div id="spec-groups">
        @foreach($groups as $g => $group)
            @php
                $items = array_values($group['items'] ?? []);
                if ($items === []) {
                    $items = [['label' => '', 'value' => '']];
                }
            @endphp
            <div class="border rounded p-3 mb-2 spec-group" data-group-index="{{ $g }}">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="flex-grow-1">
                        <label class="form-label mb-1">Group name</label>
                        <input name="spec_groups[{{ $g }}][name]" class="form-control" value="{{ $group['name'] ?? '' }}" placeholder="e.g. Capacity, Drive, Blade">
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-spec-group mt-4" title="Remove group">
                        <i class="bi bi-trash3"></i>
                    </button>
                </div>
                <div class="spec-items">
                    @foreach($items as $n => $item)
                        <div class="row g-2 mb-2 align-items-center spec-item">
                            <div class="col-md-4">
                                <input name="spec_groups[{{ $g }}][items][{{ $n }}][label]" class="form-control form-control-sm" placeholder="Label" value="{{ $item['label'] ?? '' }}">
                            </div>
                            <div class="col-md-7">
                                <input name="spec_groups[{{ $g }}][items][{{ $n }}][value]" class="form-control form-control-sm" placeholder="Value" value="{{ $item['value'] ?? '' }}">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-spec-item" title="Remove item">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary add-spec-item">
                    <i class="bi bi-plus-lg"></i> Add item
                </button>
            </div>
        @endforeach
    </div>

    <div class="d-flex justify-content-between align-items-center mt-4 mb-2">
        <h2 class="h6 mb-0">Components</h2>
        <button type="button" class="btn btn-sm btn-outline-primary" id="add-component">
            <i class="bi bi-plus-lg"></i> Add component
        </button>
    </div>
    <div id="components">
        @foreach($components as $c => $component)
            <div class="row g-2 mb-2 align-items-center component-row">
                <div class="col-md-5">
                    <input name="components[{{ $c }}][component_name]" class="form-control form-control-sm" placeholder="Component" value="{{ $component['component_name'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <input name="components[{{ $c }}][brand_name]" class="form-control form-control-sm" placeholder="Brand" value="{{ $component['brand_name'] ?? '' }}">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-component" title="Remove component">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save product</button>
        <a class="btn btn-outline-secondary" href="{{ route('products.index') }}">Cancel</a>
    </div>
</form>

<template id="tpl-spec-group">
    <div class="border rounded p-3 mb-2 spec-group" data-group-index="__G__">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
            <div class="flex-grow-1">
                <label class="form-label mb-1">Group name</label>
                <input name="spec_groups[__G__][name]" class="form-control" value="" placeholder="e.g. Capacity, Drive, Blade">
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger remove-spec-group mt-4" title="Remove group">
                <i class="bi bi-trash3"></i>
            </button>
        </div>
        <div class="spec-items">
            <div class="row g-2 mb-2 align-items-center spec-item">
                <div class="col-md-4">
                    <input name="spec_groups[__G__][items][0][label]" class="form-control form-control-sm" placeholder="Label" value="">
                </div>
                <div class="col-md-7">
                    <input name="spec_groups[__G__][items][0][value]" class="form-control form-control-sm" placeholder="Value" value="">
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-spec-item" title="Remove item">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary add-spec-item">
            <i class="bi bi-plus-lg"></i> Add item
        </button>
    </div>
</template>

<template id="tpl-spec-item">
    <div class="row g-2 mb-2 align-items-center spec-item">
        <div class="col-md-4">
            <input name="spec_groups[__G__][items][__I__][label]" class="form-control form-control-sm" placeholder="Label" value="">
        </div>
        <div class="col-md-7">
            <input name="spec_groups[__G__][items][__I__][value]" class="form-control form-control-sm" placeholder="Value" value="">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-spec-item" title="Remove item">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</template>

<template id="tpl-component">
    <div class="row g-2 mb-2 align-items-center component-row">
        <div class="col-md-5">
            <input name="components[__C__][component_name]" class="form-control form-control-sm" placeholder="Component" value="">
        </div>
        <div class="col-md-6">
            <input name="components[__C__][brand_name]" class="form-control form-control-sm" placeholder="Brand" value="">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-component" title="Remove component">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    </div>
</template>

@push('scripts')
<script>
(function () {
    const groupsEl = document.getElementById('spec-groups');
    const componentsEl = document.getElementById('components');
    const groupTpl = document.getElementById('tpl-spec-group');
    const itemTpl = document.getElementById('tpl-spec-item');
    const componentTpl = document.getElementById('tpl-component');
    let stamp = Date.now();

    function nextKey() {
        stamp += 1;
        return String(stamp);
    }

    function htmlFromTemplate(template, map) {
        let html = template.innerHTML;
        Object.keys(map).forEach(function (key) {
            html = html.split(key).join(map[key]);
        });
        return html;
    }

    document.getElementById('add-spec-group')?.addEventListener('click', function () {
        const key = nextKey();
        groupsEl.insertAdjacentHTML('beforeend', htmlFromTemplate(groupTpl, { '__G__': key }));
    });

    groupsEl?.addEventListener('click', function (event) {
        const addItem = event.target.closest('.add-spec-item');
        if (addItem) {
            const group = addItem.closest('.spec-group');
            const items = group.querySelector('.spec-items');
            const groupKey = group.dataset.groupIndex || nextKey();
            group.dataset.groupIndex = groupKey;
            items.insertAdjacentHTML('beforeend', htmlFromTemplate(itemTpl, {
                '__G__': groupKey,
                '__I__': nextKey(),
            }));
            return;
        }

        const removeItem = event.target.closest('.remove-spec-item');
        if (removeItem) {
            const group = removeItem.closest('.spec-group');
            const rows = group.querySelectorAll('.spec-item');
            if (rows.length > 1) {
                removeItem.closest('.spec-item').remove();
            } else {
                removeItem.closest('.spec-item').querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
            }
            return;
        }

        const removeGroup = event.target.closest('.remove-spec-group');
        if (removeGroup) {
            const groups = groupsEl.querySelectorAll('.spec-group');
            if (groups.length > 1) {
                removeGroup.closest('.spec-group').remove();
            } else {
                const group = removeGroup.closest('.spec-group');
                group.querySelectorAll('input').forEach(function (input) {
                    input.value = '';
                });
            }
        }
    });

    document.getElementById('add-component')?.addEventListener('click', function () {
        componentsEl.insertAdjacentHTML('beforeend', htmlFromTemplate(componentTpl, { '__C__': nextKey() }));
    });

    componentsEl?.addEventListener('click', function (event) {
        const remove = event.target.closest('.remove-component');
        if (!remove) return;
        const rows = componentsEl.querySelectorAll('.component-row');
        if (rows.length > 1) {
            remove.closest('.component-row').remove();
        } else {
            remove.closest('.component-row').querySelectorAll('input').forEach(function (input) {
                input.value = '';
            });
        }
    });
})();
</script>
@endpush
