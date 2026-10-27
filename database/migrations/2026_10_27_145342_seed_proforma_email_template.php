<?php

use App\Support\DefaultEmailTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $template = collect(DefaultEmailTemplates::catalog())
            ->firstWhere('document_type', 'proforma');

        if (! $template) {
            return;
        }

        $now = now();

        DB::table('email_templates')->updateOrInsert(
            ['code' => $template['code']],
            [
                'name' => $template['name'],
                'document_type' => $template['document_type'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }

    public function down(): void
    {
        DB::table('email_templates')->where('code', 'proforma')->delete();
    }
};
