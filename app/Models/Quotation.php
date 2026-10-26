<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'quotation_number', 'revision', 'parent_quotation_id', 'quotation_date', 'valid_until',
        'customer_id', 'customer_name', 'customer_gstin', 'customer_address', 'customer_mobile',
        'customer_email', 'subject', 'salesperson_id', 'payment_term_id', 'place_of_supply_state',
        'place_of_supply_code', 'tax_type', 'subtotal', 'discount_amount', 'taxable_amount',
        'cgst_amount', 'sgst_amount', 'igst_amount', 'round_off', 'grand_total',
        'currency_code', 'exchange_rate', 'terms_content', 'show_images', 'show_bank_details',
        'status', 'win_loss_reason', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'quotation_date' => 'date',
            'valid_until' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
            'show_images' => 'boolean',
            'show_bank_details' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class)->orderBy('sr_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(QuotationCharge::class);
    }
}