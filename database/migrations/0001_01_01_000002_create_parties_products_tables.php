<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_code')->unique();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable();
            $table->enum('gst_registration_type', ['regular', 'composition', 'unregistered', 'sez', 'export'])->default('regular');
            $table->string('contact_person')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->foreignId('payment_term_id')->nullable()->constrained('payment_terms')->nullOnDelete();
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->foreignId('salesperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping'])->default('billing');
            $table->string('label')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('state_code', 2)->nullable();
            $table->string('pin', 10)->nullable();
            $table->string('country')->default('India');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code')->unique();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('gstin', 15)->nullable()->index();
            $table->string('pan', 10)->nullable();
            $table->enum('gst_registration_type', ['regular', 'composition', 'unregistered', 'sez', 'export'])->default('regular');
            $table->string('contact_person')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->string('bank_ifsc')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('supplier_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['billing', 'shipping'])->default('billing');
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->string('state_code', 2)->nullable();
            $table->string('pin', 10)->nullable();
            $table->string('country')->default('India');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('model_number')->nullable()->index();
            $table->enum('product_type', ['goods', 'service'])->default('goods');
            $table->enum('goods_type', ['machine', 'spare', 'consumable', 'accessory'])->nullable();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignId('hsn_sac_id')->nullable()->constrained('hsn_sac_codes')->nullOnDelete();
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->foreignId('uom_id')->nullable()->constrained('uoms')->nullOnDelete();
            $table->text('description')->nullable();
            $table->decimal('list_price', 15, 2)->default(0);
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('min_selling_price', 15, 2)->nullable();
            $table->decimal('default_discount_percent', 5, 2)->default(0);
            $table->boolean('track_serial')->default(false);
            $table->decimal('reorder_level', 15, 4)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_spec_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_spec_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spec_group_id')->constrained('product_spec_groups')->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->text('value');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('component_name');
            $table->string('brand_name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('type')->nullable();
            $table->timestamps();
        });

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 15, 2);
            $table->timestamps();
            $table->unique(['price_list_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');
        Schema::dropIfExists('product_attachments');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_components');
        Schema::dropIfExists('product_spec_items');
        Schema::dropIfExists('product_spec_groups');
        Schema::dropIfExists('products');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('supplier_addresses');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customer_addresses');
        Schema::dropIfExists('customers');
    }
};
