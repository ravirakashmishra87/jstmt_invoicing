<?php

use App\Support\DefaultEmailTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (DefaultEmailTemplates::catalog() as $template) {
            DB::table('email_templates')
                ->where('code', $template['code'])
                ->update([
                    'name' => $template['name'],
                    'document_type' => $template['document_type'],
                    'subject' => $template['subject'],
                    'body' => $template['body'],
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left empty — prior copy was plain marketing text.
    }
};
