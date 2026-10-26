<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceTaxSummary extends Model
{
    protected $table = 'invoice_tax_summary';

    protected $fillable = [
        'invoice_id', 'hsn_sac', 'taxable_value', 'tax_rate',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'tax_amount',
    ];

    protected function casts(): array
    {
        return [
            'taxable_value' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}