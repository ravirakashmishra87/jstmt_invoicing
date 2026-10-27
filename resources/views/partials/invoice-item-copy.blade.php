@php
    use App\Support\RichText;
    $itemCode = $item->item_code ?: $item->product?->sku;
    $itemName = $item->product_name ?: $item->product?->name;
    $descriptionPlain = RichText::plain($item->description);
@endphp
@if($itemCode)
    <div class="strong">{{ $itemCode }}</div>
@endif
@if($itemName)
    <div @class(['strong' => ! $itemCode, 'mt-1' => (bool) $itemCode])>{{ $itemName }}</div>
@endif
@if($descriptionPlain !== '' && $descriptionPlain !== $itemName && $descriptionPlain !== $itemCode)
    <div class="item-desc muted" style="margin-top:3px;">{!! nl2br(e($descriptionPlain)) !!}</div>
@elseif(! $itemCode && ! $itemName && $descriptionPlain !== '')
    <div class="item-desc">{!! nl2br(e($descriptionPlain)) !!}</div>
@endif
@if(!empty($showSecondary) && $item->secondary_description)
    <div class="muted" style="margin-top:2px;">{{ $item->secondary_description }}</div>
@endif
