<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 2)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('company_profile', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('trade_name')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('state_code', 2)->nullable();
            $table->string('pin', 10)->nullable();
            $table->string('country')->default('India');
            $table->string('gstin', 15)->nullable();
            $table->string('pan', 10)->nullable();
            $table->string('udyam_no')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('brand_logo_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->string('stamp_path')->nullable();
            $table->text('declaration')->nullable();
            $table->string('jurisdiction')->nullable();
            $table->text('footer_text')->nullable();
            $table->unsignedInteger('quotation_validity_days')->default(7);
            $table->string('stock_valuation')->default('weighted_average');
            $table->boolean('prevent_negative_stock')->default(true);
            $table->boolean('credit_limit_hard_block')->default(false);
            $table->boolean('overdue_interest_enabled')->default(false);
            $table->decimal('overdue_interest_rate', 5, 2)->default(24);
            $table->decimal('eway_bill_threshold', 15, 2)->default(50000);
            $table->string('round_off_rule')->default('nearest');
            $table->date('books_locked_upto')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('account_holder');
            $table->string('bank_name');
            $table->string('branch')->nullable();
            $table->string('account_number');
            $table->string('ifsc', 20)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('address')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
        });

        Schema::create('number_series', function (Blueprint $table) {
            $table->id();
            $table->string('document_type')->unique();
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->string('separator')->default('/');
            $table->string('format_pattern')->nullable();
            $table->unsignedInteger('starting_number')->default(1);
            $table->unsignedInteger('current_number')->default(0);
            $table->unsignedTinyInteger('zero_padding')->default(0);
            $table->string('reset_rule')->default('financial_year');
            $table->string('last_reset_key')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->timestamps();
        });

        Schema::create('uoms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hsn_sac_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['HSN', 'SAC'])->default('HSN');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_terms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('advance_percent', 5, 2)->default(0);
            $table->decimal('balance_percent', 5, 2)->default(100);
            $table->unsignedInteger('due_days')->default(0);
            $table->string('milestone')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_modes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('charge_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->foreignId('hsn_sac_id')->nullable()->constrained('hsn_sac_codes')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('transporters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('gstin', 15)->nullable();
            $table->string('transporter_id')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('terms_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type')->default('quotation');
            $table->longText('content');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terms_templates');
        Schema::dropIfExists('transporters');
        Schema::dropIfExists('charge_types');
        Schema::dropIfExists('payment_modes');
        Schema::dropIfExists('payment_terms');
        Schema::dropIfExists('hsn_sac_codes');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('uoms');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('number_series');
        Schema::dropIfExists('financial_years');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('company_profile');
        Schema::dropIfExists('states');
    }
};
