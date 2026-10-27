<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaTaxSummary extends Model
{
    protected $table = 'proforma_tax_summary';

    protected $fillable = [
        'proforma_id', 'hsn_sac', 'taxable_value', 'tax_rate',
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

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }
}
