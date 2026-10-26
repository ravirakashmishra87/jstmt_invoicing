<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('serial_number');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->enum('status', ['in_stock', 'reserved', 'sold', 'returned', 'under_repair', 'scrapped'])->default('in_stock');
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->string('grn_reference')->nullable();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'serial_number']);
            $table->index('status');
        });

        Schema::table('invoice_item_serials', function (Blueprint $table) {
            $table->foreign('product_serial_id')->references('id')->on('product_serials')->nullOnDelete();
        });

        Schema::create('stock_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->decimal('quantity_on_hand', 15, 4)->default(0);
            $table->decimal('quantity_reserved', 15, 4)->default(0);
            $table->decimal('average_cost', 15, 4)->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->string('serial_number')->nullable();
            $table->decimal('qty_in', 15, 4)->default(0);
            $table->decimal('qty_out', 15, 4)->default(0);
            $table->decimal('balance_qty', 15, 4)->default(0);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('document_number')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['product_id', 'transaction_date']);
            $table->index(['document_type', 'document_id']);
        });

        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('adjustment_number')->unique();
            $table->date('adjustment_date');
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('reason')->nullable();
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->decimal('quantity_diff', 15, 4);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->date('receipt_date');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->foreignId('payment_mode_id')->nullable()->constrained('payment_modes')->nullOnDelete();
            $table->string('reference_no')->nullable();
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->decimal('unallocated_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('posted');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('allocation_type')->default('invoice');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type')->nullable();
            $table->string('title');
            $table->text('message')->nullable();
            $table->string('link')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('to_email');
            $table->string('subject');
            $table->string('document_type')->nullable();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed'])->default('queued');
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
        Schema::dropIfExists('stock_ledger');
        Schema::dropIfExists('stock_balances');
        Schema::table('invoice_item_serials', function (Blueprint $table) {
            $table->dropForeign(['product_serial_id']);
        });
        Schema::dropIfExists('product_serials');
    }
};
