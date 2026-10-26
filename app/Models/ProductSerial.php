<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductSerial extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id', 'serial_number', 'warehouse_id', 'status', 'purchase_cost',
        'grn_reference', 'invoice_id', 'warranty_start', 'warranty_end', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_cost' => 'decimal:2',
            'warranty_start' => 'date',
            'warranty_end' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}