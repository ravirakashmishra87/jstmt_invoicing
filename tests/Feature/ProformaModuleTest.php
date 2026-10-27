<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Permission;
use App\Models\Proforma;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProformaModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_proforma_index(): void
    {
        $this->get(route('proformas.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authorized_user_can_view_proforma_index(): void
    {
        $user = $this->userWithPermissions(['proformas.view']);

        $this->actingAs($user)
            ->get(route('proformas.index'))
            ->assertOk()
            ->assertSee('Proforma invoices');
    }

    public function test_unauthorized_user_cannot_view_proforma_index(): void
    {
        $role = Role::create([
            'name' => 'Viewer',
            'slug' => 'viewer',
            'description' => 'No proforma access',
        ]);

        $user = User::create([
            'name' => 'No Access',
            'email' => 'no-access@example.com',
            'username' => 'noaccess',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('proformas.index'))
            ->assertForbidden();
    }

    public function test_create_form_hides_quotations_that_already_have_a_proforma(): void
    {
        $user = $this->userWithPermissions(['proformas.create']);
        $customer = $this->makeCustomer();
        $open = $this->makeQuotation($customer, 'Q-OPEN-1');
        $taken = $this->makeQuotation($customer, 'Q-TAKEN-1');

        Proforma::create([
            'proforma_number' => 'PI/26-27/001',
            'proforma_date' => now()->toDateString(),
            'quotation_id' => $taken->id,
            'customer_id' => $customer->id,
            'buyer_name' => $customer->legal_name,
            'status' => 'draft',
            'grand_total' => 0,
        ]);

        $this->actingAs($user)
            ->get(route('proformas.create'))
            ->assertOk()
            ->assertSee('Q-OPEN-1')
            ->assertDontSee('Q-TAKEN-1');
    }

    public function test_selecting_quotation_prefills_line_items_on_create(): void
    {
        $user = $this->userWithPermissions(['proformas.create']);
        $customer = $this->makeCustomer();
        $quotation = $this->makeQuotation($customer, 'Q-FILL-1');

        QuotationItem::create([
            'quotation_id' => $quotation->id,
            'sr_no' => 1,
            'product_name' => 'Band Saw',
            'description' => 'Heavy duty band saw machine',
            'quantity' => 2,
            'uom' => 'NOS',
            'rate' => 100000,
            'discount_percent' => 0,
            'tax_rate' => 18,
        ]);

        $this->actingAs($user)
            ->get(route('proformas.create', ['quotation_id' => $quotation->id]))
            ->assertOk()
            ->assertSee('Heavy duty band saw machine')
            ->assertSee('Q-FILL-1')
            ->assertSee($customer->legal_name);
    }

    /**
     * @param  list<string>  $slugs
     */
    private function userWithPermissions(array $slugs): User
    {
        $role = Role::create([
            'name' => 'Accounts',
            'slug' => 'accounts-test-'.uniqid(),
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
            'name' => 'Accounts User',
            'email' => 'accounts-'.uniqid().'@example.com',
            'username' => 'accounts_'.uniqid(),
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    private function makeCustomer(): Customer
    {
        return Customer::create([
            'customer_code' => 'C'.uniqid(),
            'legal_name' => 'Acme Tools Pvt Ltd',
            'is_active' => true,
        ]);
    }

    private function makeQuotation(Customer $customer, string $number): Quotation
    {
        return Quotation::create([
            'quotation_number' => $number,
            'quotation_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'customer_name' => $customer->legal_name,
            'status' => 'sent',
            'grand_total' => 0,
        ]);
    }
}
