<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proforma extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'proforma_number', 'proforma_date', 'valid_until', 'financial_year_id', 'quotation_id',
        'customer_id', 'buyer_name', 'buyer_address', 'buyer_gstin', 'buyer_state', 'buyer_state_code',
        'buyer_contact', 'consignee_name', 'consignee_address', 'consignee_gstin', 'consignee_state',
        'consignee_state_code', 'consignee_contact', 'place_of_supply', 'place_of_supply_code',
        'tax_type', 'reference_no', 'reference_date', 'buyer_order_no', 'buyer_order_date',
        'destination', 'terms_of_delivery', 'mode_terms_of_payment', 'payment_term_id', 'due_date',
        'subtotal', 'discount_amount', 'taxable_amount', 'cgst_amount', 'sgst_amount', 'igst_amount',
        'round_off', 'grand_total', 'amount_in_words', 'tax_amount_in_words', 'currency_code',
        'reverse_charge', 'supply_type', 'status', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'proforma_date' => 'date',
            'valid_until' => 'date',
            'reference_date' => 'date',
            'buyer_order_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'reverse_charge' => 'boolean',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaItem::class)->orderBy('sr_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(ProformaCharge::class);
    }

    public function taxSummary(): HasMany
    {
        return $this->hasMany(ProformaTaxSummary::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'sent'], true);
    }
}
