<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->string('proforma_number')->unique();
            $table->date('proforma_date');
            $table->date('valid_until')->nullable();
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
            $table->string('tax_type', 20)->default('cgst_sgst');
            $table->string('reference_no')->nullable();
            $table->date('reference_date')->nullable();
            $table->string('buyer_order_no')->nullable();
            $table->date('buyer_order_date')->nullable();
            $table->string('destination')->nullable();
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
            $table->string('amount_in_words')->nullable();
            $table->string('tax_amount_in_words')->nullable();
            $table->string('currency_code', 3)->default('INR');
            $table->boolean('reverse_charge')->default(false);
            $table->string('supply_type', 10)->default('B2B');
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('proforma_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sr_no')->default(1);
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('item_code')->nullable();
            $table->string('product_name')->nullable();
            $table->longText('description')->nullable();
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

        Schema::create('proforma_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained()->cascadeOnDelete();
            $table->foreignId('charge_type_id')->nullable()->constrained('charge_types')->nullOnDelete();
            $table->string('name');
            $table->string('hsn_sac')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('proforma_tax_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proforma_id')->constrained()->cascadeOnDelete();
            $table->string('hsn_sac');
            $table->decimal('taxable_value', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('proforma_id')->nullable()->after('quotation_id')->constrained('proformas')->nullOnDelete();
        });

        $now = now();
        DB::table('number_series')->updateOrInsert(
            ['document_type' => 'proforma'],
            [
                'prefix' => '',
                'suffix' => '',
                'format_pattern' => 'PI/{FY}/{SEQ}',
                'separator' => '/',
                'starting_number' => 1,
                'current_number' => 0,
                'zero_padding' => 3,
                'reset_rule' => 'financial_year',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'print'];
        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'proformas.'.$action],
                [
                    'module' => 'proformas',
                    'action' => $action,
                    'name' => 'Proformas '.str_replace('_', ' ', $action),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')->where('module', 'proformas')->pluck('id');
        foreach (['super-admin', 'accounts', 'sales-executive'] as $roleSlug) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }
            $slugs = match ($roleSlug) {
                'super-admin' => $actions,
                'accounts' => ['view', 'create', 'edit', 'print', 'approve'],
                'sales-executive' => ['view', 'create', 'edit', 'print'],
                default => ['view'],
            };
            foreach ($slugs as $action) {
                $permissionId = DB::table('permissions')->where('slug', 'proformas.'.$action)->value('id');
                if ($permissionId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $permissionId],
                        []
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->where('module', 'proformas')->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        DB::table('number_series')->where('document_type', 'proforma')->delete();

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proforma_id');
        });

        Schema::dropIfExists('proforma_tax_summary');
        Schema::dropIfExists('proforma_charges');
        Schema::dropIfExists('proforma_items');
        Schema::dropIfExists('proformas');
    }
};
