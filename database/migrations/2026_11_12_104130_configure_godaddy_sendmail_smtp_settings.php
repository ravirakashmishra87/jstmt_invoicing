<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('smtp_settings')) {
            return;
        }

        $payload = [
            'mailer' => 'sendmail',
            'host' => null,
            'port' => 25,
            'encryption' => 'none',
            'username' => null,
            'password' => null,
            'from_name' => 'JST Machine Tools',
            'from_email' => 'info@jstmachinetools.com',
            'reply_to_email' => 'info@jstmachinetools.com',
            'is_active' => true,
            'updated_at' => now(),
        ];

        if (! Schema::hasColumn('smtp_settings', 'mailer')) {
            unset($payload['mailer']);
        }

        DB::table('smtp_settings')->update($payload);

        if (Schema::hasTable('company_profiles') && Schema::hasColumn('company_profiles', 'email')) {
            DB::table('company_profiles')
                ->where(function ($query): void {
                    $query->whereNull('email')->orWhere('email', '');
                })
                ->update([
                    'email' => 'info@jstmachinetools.com',
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Intentionally left blank — production mailbox should remain configured.
    }
};
