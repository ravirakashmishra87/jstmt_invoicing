<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLedger extends Model
{
    protected $table = 'stock_ledger';

    protected $fillable = [
        'transaction_date', 'product_id', 'warehouse_id', 'product_serial_id', 'serial_number',
        'qty_in', 'qty_out', 'balance_qty', 'unit_cost', 'document_type', 'document_id',
        'document_number', 'remarks', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'qty_in' => 'decimal:4',
            'qty_out' => 'decimal:4',
            'balance_qty' => 'decimal:4',
            'unit_cost' => 'decimal:4',
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
}