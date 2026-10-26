<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_charges', function (Blueprint $table) {
            $table->decimal('rate', 15, 4)->default(0)->after('uom');
        });

        foreach (DB::table('quotation_charges')->orderBy('id')->get() as $charge) {
            $qty = (float) ($charge->quantity ?: 1);
            if ($qty <= 0) {
                $qty = 1;
            }
            DB::table('quotation_charges')->where('id', $charge->id)->update([
                'rate' => round(((float) $charge->amount) / $qty, 4),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('quotation_charges', function (Blueprint $table) {
            $table->dropColumn('rate');
        });
    }
};
