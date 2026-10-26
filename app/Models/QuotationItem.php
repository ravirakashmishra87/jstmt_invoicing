<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItem extends Model
{
    protected $fillable = [
        'quotation_id', 'sr_no', 'product_id', 'product_name', 'model_number', 'description',
        'hsn_sac', 'uom', 'quantity', 'list_price', 'discount_percent', 'discount_amount',
        'rate', 'amount', 'tax_rate', 'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'list_price' => 'decimal:4',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'rate' => 'decimal:4',
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}