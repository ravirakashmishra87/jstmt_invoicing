<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_charges', function (Blueprint $table) {
            $table->decimal('quantity', 15, 4)->default(1)->after('name');
            $table->string('uom')->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_charges', function (Blueprint $table) {
            $table->dropColumn(['quantity', 'uom']);
        });
    }
};
