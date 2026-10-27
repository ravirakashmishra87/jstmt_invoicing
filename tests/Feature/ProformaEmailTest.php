<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Permission;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\Role;
use App\Models\User;
use App\Services\DocumentMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProformaEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_proforma_email_template_is_seeded(): void
    {
        $template = EmailTemplate::query()->where('document_type', 'proforma')->first();

        $this->assertNotNull($template);
        $this->assertSame('proforma', $template->code);
        $this->assertStringContainsString('{{document_number}}', $template->subject);
    }

    public function test_mail_service_resolves_proforma_document(): void
    {
        $customer = Customer::create([
            'customer_code' => 'C-MAIL-1',
            'legal_name' => 'Mail Customer',
            'email' => 'buyer@example.com',
            'is_active' => true,
        ]);

        $quotation = Quotation::create([
            'quotation_number' => 'Q-MAIL-1',
            'quotation_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->legal_name,
            'status' => 'sent',
            'grand_total' => 0,
        ]);

        $proforma = Proforma::create([
            'proforma_number' => 'PI/26-27/099',
            'proforma_date' => now()->toDateString(),
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'buyer_name' => $customer->legal_name,
            'status' => 'draft',
            'grand_total' => 125000,
        ]);

        $resolved = app(DocumentMailService::class)->resolveDocument('proforma', $proforma->id);

        $this->assertSame('buyer@example.com', $resolved['to']);
        $this->assertSame('PI/26-27/099', $resolved['document_number']);
        $this->assertSame('Mail Customer', $resolved['customer_name']);
    }

    public function test_proforma_show_includes_email_action_for_authorized_user(): void
    {
        $user = $this->userWithPermissions(['proformas.view', 'communication.create']);
        $customer = Customer::create([
            'customer_code' => 'C-MAIL-2',
            'legal_name' => 'Show Customer',
            'email' => 'show@example.com',
            'is_active' => true,
        ]);
        $quotation = Quotation::create([
            'quotation_number' => 'Q-MAIL-2',
            'quotation_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->legal_name,
            'status' => 'sent',
            'grand_total' => 0,
        ]);
        $proforma = Proforma::create([
            'proforma_number' => 'PI/26-27/100',
            'proforma_date' => now()->toDateString(),
            'quotation_id' => $quotation->id,
            'customer_id' => $customer->id,
            'buyer_name' => $customer->legal_name,
            'status' => 'draft',
            'grand_total' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('proformas.show', $proforma))
            ->assertOk()
            ->assertSee(route('communication.send-document', ['type' => 'proforma', 'id' => $proforma->id]), false);
    }

    /**
     * @param  list<string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::create([
            'name' => 'Mail Tester',
            'slug' => 'mail-tester-'.uniqid(),
            'description' => 'Test role',
        ]);

        foreach ($slugs as $slug) {
            [$module, $action] = explode('.', $slug, 2);
            $permission = Permission::query()->firstOrCreate(
                ['slug' => $slug],
                [
                    'module' => $module,
                    'action' => $action,
                    'name' => ucfirst($module).' '.$action,
                ]
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        return User::create([
            'name' => 'Mail User',
            'email' => 'mail-'.uniqid().'@example.com',
            'username' => 'mail_'.uniqid(),
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }
}
