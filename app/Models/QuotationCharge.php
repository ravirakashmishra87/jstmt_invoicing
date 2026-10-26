<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationCharge extends Model
{
    protected $fillable = [
        'quotation_id', 'charge_type_id', 'name', 'quantity', 'uom', 'rate', 'hsn_sac', 'is_at_actual', 'at_actual_text',
        'percent_of_invoice', 'amount', 'tax_rate', 'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'is_at_actual' => 'boolean',
            'quantity' => 'decimal:4',
            'rate' => 'decimal:4',
            'percent_of_invoice' => 'decimal:4',
            'amount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }
}