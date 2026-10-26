<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_number')->unique();
            $table->unsignedInteger('revision')->default(0);
            $table->foreignId('parent_quotation_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->date('quotation_date');
            $table->date('valid_until')->nullable();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_name');
            $table->string('customer_gstin', 15)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('subject')->nullable();
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->string('place_of_supply_state')->nullable();
            $table->string('place_of_supply_code', 2)->nullable();
            $table->enum('tax_type', ['igst', 'cgst_sgst'])->default('igst');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->longText('terms_content')->nullable();
            $table->boolean('show_images')->default(true);
            $table->boolean('show_bank_details')->default(true);
            $table->enum('status', ['draft', 'sent', 'under_negotiation', 'accepted', 'rejected', 'expired', 'revised'])->default('draft');
            $table->string('win_loss_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['quotation_date', 'status']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sr_no')->default(1);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('model_number')->nullable();
            $table->longText('description')->nullable();
            $table->string('hsn_sac')->nullable();
            $table->string('uom')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->decimal('list_price', 15, 4)->default(0);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('rate', 15, 4)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('quotation_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->string('name');
            $table->string('hsn_sac')->nullable();
            $table->boolean('is_at_actual')->default(false);
            $table->string('at_actual_text')->nullable();
            $table->decimal('percent_of_invoice', 8, 4)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->nullOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('buyer_name');
            $table->text('buyer_address')->nullable();
            $table->string('buyer_gstin', 15)->nullable();
            $table->string('buyer_state')->nullable();
            $table->string('buyer_state_code', 2)->nullable();
            $table->string('buyer_contact')->nullable();
            $table->string('consignee_name')->nullable();
            $table->text('consignee_address')->nullable();
            $table->string('consignee_gstin', 15)->nullable();
            $table->string('consignee_state')->nullable();
            $table->string('consignee_state_code', 2)->nullable();
            $table->string('consignee_contact')->nullable();
            $table->string('place_of_supply')->nullable();
            $table->string('place_of_supply_code', 2)->nullable();
            $table->enum('tax_type', ['igst', 'cgst_sgst'])->default('igst');
            $table->string('delivery_note')->nullable();
            $table->date('delivery_note_date')->nullable();
            $table->string('reference_no')->nullable();
            $table->date('reference_date')->nullable();
            $table->string('other_references')->nullable();
            $table->string('buyer_order_no')->nullable();
            $table->date('buyer_order_date')->nullable();
            $table->string('dispatch_doc_no')->nullable();
            $table->string('dispatched_through')->nullable();
            $table->string('destination')->nullable();
            $table->string('lr_rr_no')->nullable();
            $table->date('lr_rr_date')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('terms_of_delivery')->nullable();
            $table->string('mode_terms_of_payment')->nullable();
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('taxable_amount', 15, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->string('amount_in_words')->nullable();
            $table->string('tax_amount_in_words')->nullable();
            $table->string('currency_code', 3)->default('INR');
            $table->decimal('exchange_rate', 12, 6)->default(1);
            $table->boolean('reverse_charge')->default(false);
            $table->string('supply_type')->default('B2B');
            $table->string('irn')->nullable();
            $table->string('ack_no')->nullable();
            $table->dateTime('ack_date')->nullable();
            $table->string('signed_qr_path')->nullable();
            $table->string('ewb_number')->nullable();
            $table->dateTime('ewb_date')->nullable();
            $table->dateTime('ewb_valid_upto')->nullable();
            $table->enum('status', ['draft', 'issued', 'partially_paid', 'paid', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['invoice_date', 'status']);
            $table->index('customer_id');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sr_no')->default(1);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->string('secondary_description')->nullable();
            $table->string('hsn_sac')->nullable();
            $table->decimal('quantity', 15, 4)->default(1);
            $table->string('uom')->nullable();
            $table->decimal('rate', 15, 4)->default(0);
            $table->decimal('discount_percent', 8, 4)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cgst_rate', 5, 2)->default(0);
            $table->decimal('sgst_rate', 5, 2)->default(0);
            $table->decimal('igst_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->boolean('is_service')->default(false);
            $table->timestamps();
        });

        Schema::create('invoice_item_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_serial_id')->nullable();
            $table->string('serial_number');
            $table->timestamps();
        });

        Schema::create('invoice_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->string('name');
            $table->string('hsn_sac')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_tax_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('hsn_sac');
            $table->decimal('taxable_value', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_tax_summary');
        Schema::dropIfExists('invoice_charges');
        Schema::dropIfExists('invoice_item_serials');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('quotation_charges');
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
