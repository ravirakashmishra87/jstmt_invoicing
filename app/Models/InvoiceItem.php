<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'sr_no', 'product_id', 'description', 'secondary_description', 'hsn_sac',
        'quantity', 'uom', 'rate', 'discount_percent', 'discount_amount', 'amount',
        'tax_rate', 'cgst_rate', 'sgst_rate', 'igst_rate', 'cgst_amount', 'sgst_amount',
        'igst_amount', 'tax_amount', 'is_service',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'discount_percent' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'cgst_rate' => 'decimal:2',
            'sgst_rate' => 'decimal:2',
            'igst_rate' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'is_service' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function serials(): HasMany
    {
        return $this->hasMany(InvoiceItemSerial::class);
    }
}