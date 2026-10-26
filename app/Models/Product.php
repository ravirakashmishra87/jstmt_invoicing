<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'sku', 'name', 'model_number', 'product_type', 'goods_type', 'category_id', 'brand_id',
        'hsn_sac_id', 'tax_rate_id', 'uom_id', 'description', 'list_price', 'purchase_price',
        'min_selling_price', 'default_discount_percent', 'track_serial', 'reorder_level',
        'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'list_price' => 'decimal:2',
            'purchase_price' => 'decimal:2',
            'min_selling_price' => 'decimal:2',
            'default_discount_percent' => 'decimal:2',
            'reorder_level' => 'decimal:4',
            'track_serial' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function hsnSac(): BelongsTo
    {
        return $this->belongsTo(HsnSacCode::class, 'hsn_sac_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function uom(): BelongsTo
    {
        return $this->belongsTo(Uom::class);
    }

    public function specGroups(): HasMany
    {
        return $this->hasMany(ProductSpecGroup::class)->orderBy('sort_order');
    }

    public function components(): HasMany
    {
        return $this->hasMany(ProductComponent::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): ?ProductImage
    {
        return $this->images->firstWhere('is_primary', true) ?? $this->images->first();
    }

    public function primaryImageUrl(): ?string
    {
        $image = $this->relationLoaded('images') ? $this->primaryImage() : $this->images()->orderByDesc('is_primary')->orderBy('sort_order')->first();
        if (! $image || ! $image->path) {
            return null;
        }

        return asset('storage/'.$image->path);
    }

    public function primaryImageAbsolutePath(): ?string
    {
        $image = $this->relationLoaded('images') ? $this->primaryImage() : $this->images()->orderByDesc('is_primary')->orderBy('sort_order')->first();
        if (! $image || ! $image->path) {
            return null;
        }

        $absolute = storage_path('app/public/'.$image->path);

        return is_file($absolute) ? $absolute : null;
    }

    public function serials(): HasMany
    {
        return $this->hasMany(ProductSerial::class);
    }

    public function stockBalances(): HasMany
    {
        return $this->hasMany(StockBalance::class);
    }

    public function availableSerials()
    {
        return $this->serials()->where('status', 'in_stock');
    }
}