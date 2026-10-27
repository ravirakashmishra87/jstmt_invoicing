<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('item_code')->nullable()->after('product_id');
            $table->string('product_name')->nullable()->after('item_code');
        });

        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement('ALTER TABLE invoice_items MODIFY description LONGTEXT NULL');
            DB::statement('
                UPDATE invoice_items
                INNER JOIN products ON products.id = invoice_items.product_id
                SET invoice_items.item_code = COALESCE(invoice_items.item_code, products.sku),
                    invoice_items.product_name = COALESCE(invoice_items.product_name, products.name)
                WHERE invoice_items.product_id IS NOT NULL
            ');
        } else {
            $items = DB::table('invoice_items')->whereNotNull('product_id')->get(['id', 'product_id']);
            foreach ($items as $item) {
                $product = DB::table('products')->where('id', $item->product_id)->first(['sku', 'name']);
                if (! $product) {
                    continue;
                }
                DB::table('invoice_items')->where('id', $item->id)->update([
                    'item_code' => $product->sku,
                    'product_name' => $product->name,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['item_code', 'product_name']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE invoice_items MODIFY description VARCHAR(255) NULL');
        }
    }
};
