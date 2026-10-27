<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use Auditable, SoftDeletes;

    protected $fillable = [
        'invoice_number', 'invoice_date', 'financial_year_id', 'quotation_id', 'proforma_id', 'customer_id',
        'buyer_name', 'buyer_address', 'buyer_gstin', 'buyer_state', 'buyer_state_code', 'buyer_contact',
        'consignee_name', 'consignee_address', 'consignee_gstin', 'consignee_state',
        'consignee_state_code', 'consignee_contact', 'place_of_supply', 'place_of_supply_code',
        'tax_type', 'delivery_note', 'delivery_note_date', 'reference_no', 'reference_date',
        'other_references', 'buyer_order_no', 'buyer_order_date', 'dispatch_doc_no',
        'dispatched_through', 'destination', 'lr_rr_no', 'lr_rr_date', 'vehicle_no',
        'terms_of_delivery', 'mode_terms_of_payment', 'payment_term_id', 'due_date',
        'subtotal', 'discount_amount', 'taxable_amount', 'cgst_amount', 'sgst_amount',
        'igst_amount', 'round_off', 'grand_total', 'amount_paid', 'balance_due',
        'amount_in_words', 'tax_amount_in_words', 'currency_code', 'exchange_rate',
        'reverse_charge', 'supply_type', 'irn', 'ack_no', 'ack_date', 'signed_qr_path',
        'ewb_number', 'ewb_date', 'ewb_valid_upto', 'status', 'notes', 'created_by',
        'updated_by', 'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'delivery_note_date' => 'date',
            'reference_date' => 'date',
            'buyer_order_date' => 'date',
            'lr_rr_date' => 'date',
            'due_date' => 'date',
            'ack_date' => 'datetime',
            'ewb_date' => 'datetime',
            'ewb_valid_upto' => 'datetime',
            'issued_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'cgst_amount' => 'decimal:2',
            'sgst_amount' => 'decimal:2',
            'igst_amount' => 'decimal:2',
            'round_off' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'exchange_rate' => 'decimal:6',
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

    public function proforma(): BelongsTo
    {
        return $this->belongsTo(Proforma::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sr_no');
    }

    public function charges(): HasMany
    {
        return $this->hasMany(InvoiceCharge::class);
    }

    public function taxSummary(): HasMany
    {
        return $this->hasMany(InvoiceTaxSummary::class);
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }
}
