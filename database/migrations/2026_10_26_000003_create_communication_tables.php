<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('smtp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->default(587);
            $table->string('encryption', 20)->default('tls');
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to_email')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('document_type');
            $table->string('subject');
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('email_logs', function (Blueprint $table) {
            $table->string('cc_email')->nullable()->after('to_email');
            $table->text('body')->nullable()->after('subject');
            $table->string('attachment_name')->nullable()->after('document_id');
        });

        DB::table('smtp_settings')->insert([
            'host' => null,
            'port' => 587,
            'encryption' => 'tls',
            'username' => null,
            'password' => null,
            'from_name' => 'JST Machine Tools',
            'from_email' => null,
            'reply_to_email' => null,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (\App\Support\DefaultEmailTemplates::catalog() as $template) {
            DB::table('email_templates')->insert([
                ...$template,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'print'];
        foreach ($actions as $action) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => 'communication.'.$action],
                [
                    'module' => 'communication',
                    'action' => $action,
                    'name' => 'Communication '.str_replace('_', ' ', $action),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $superRoleId = DB::table('roles')->where('slug', 'super-admin')->value('id');
        if ($superRoleId) {
            $permissionIds = DB::table('permissions')->where('module', 'communication')->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superRoleId, 'permission_id' => $permissionId],
                    []
                );
            }
        }

        $accountsRoleId = DB::table('roles')->where('slug', 'accounts')->value('id');
        if ($accountsRoleId) {
            foreach (['communication.view', 'communication.create', 'communication.print'] as $slug) {
                $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
                if ($permissionId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $accountsRoleId, 'permission_id' => $permissionId],
                        []
                    );
                }
            }
        }

        $salesRoleId = DB::table('roles')->where('slug', 'sales-executive')->value('id');
        if ($salesRoleId) {
            foreach (['communication.view', 'communication.create', 'communication.print'] as $slug) {
                $permissionId = DB::table('permissions')->where('slug', $slug)->value('id');
                if ($permissionId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $salesRoleId, 'permission_id' => $permissionId],
                        []
                    );
                }
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->where('module', 'communication')->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        Schema::table('email_logs', function (Blueprint $table) {
            $table->dropColumn(['cc_email', 'body', 'attachment_name']);
        });

        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('smtp_settings');
    }
};
