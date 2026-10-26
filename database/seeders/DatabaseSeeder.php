<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\ChargeType;
use App\Models\CompanyProfile;
use App\Models\Customer;
use App\Models\FinancialYear;
use App\Models\HsnSacCode;
use App\Models\NumberSeries;
use App\Models\PaymentMode;
use App\Models\PaymentTerm;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\Role;
use App\Models\State;
use App\Models\TaxRate;
use App\Models\TermsTemplate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedStates();
            $this->seedAccess();
            $admin = $this->seedAdmin();
            $gujarat = State::where('code', '24')->firstOrFail();
            $maharashtra = State::where('code', '27')->firstOrFail();
            $this->seedCompany($gujarat);
            $warehouse = $this->seedOperations();
            $tax = $this->seedMasters();
            $this->seedSample($admin, $maharashtra, $warehouse, $tax);
        });
    }

    private function seedStates(): void
    {
        $states = [
            ['01', 'Jammu and Kashmir'],
            ['02', 'Himachal Pradesh'],
            ['03', 'Punjab'],
            ['04', 'Chandigarh'],
            ['05', 'Uttarakhand'],
            ['06', 'Haryana'],
            ['07', 'Delhi'],
            ['08', 'Rajasthan'],
            ['09', 'Uttar Pradesh'],
            ['10', 'Bihar'],
            ['11', 'Sikkim'],
            ['12', 'Arunachal Pradesh'],
            ['13', 'Nagaland'],
            ['14', 'Manipur'],
            ['15', 'Mizoram'],
            ['16', 'Tripura'],
            ['17', 'Meghalaya'],
            ['18', 'Assam'],
            ['19', 'West Bengal'],
            ['20', 'Jharkhand'],
            ['21', 'Odisha'],
            ['22', 'Chhattisgarh'],
            ['23', 'Madhya Pradesh'],
            ['24', 'Gujarat'],
            ['25', 'Daman and Diu'],
            ['26', 'Dadra and Nagar Haveli and Daman and Diu'],
            ['27', 'Maharashtra'],
            ['28', 'Andhra Pradesh (Old)'],
            ['29', 'Karnataka'],
            ['30', 'Goa'],
            ['31', 'Lakshadweep'],
            ['32', 'Kerala'],
            ['33', 'Tamil Nadu'],
            ['34', 'Puducherry'],
            ['35', 'Andaman and Nicobar Islands'],
            ['36', 'Telangana'],
            ['37', 'Andhra Pradesh'],
            ['38', 'Ladakh'],
            ['97', 'Other Territory'],
        ];

        foreach ($states as [$code, $name]) {
            State::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }
    }

    private function seedAccess(): \Illuminate\Support\Collection
    {
        $modules = ['dashboard', 'customers', 'suppliers', 'products', 'quotations', 'invoices', 'stock', 'receipts', 'reports', 'communication', 'settings', 'users', 'masters'];
        $actions = ['view', 'create', 'edit', 'delete', 'approve', 'export', 'print'];

        foreach ($modules as $module) {
            foreach ($actions as $action) {
                Permission::updateOrCreate(
                    ['slug' => $module.'.'.$action],
                    [
                        'module' => $module,
                        'action' => $action,
                        'name' => ucfirst($module).' '.str_replace('_', ' ', $action),
                    ]
                );
            }
        }

        $permissions = Permission::all()->keyBy('slug');
        $sync = function (Role $role, array $map) use ($permissions): void {
            $slugs = [];
            foreach ($map as $module => $actions) {
                foreach ($actions as $action) {
                    $slugs[] = $module.'.'.$action;
                }
            }
            $role->permissions()->sync(
                collect($slugs)->map(fn (string $slug) => $permissions[$slug]->id ?? null)->filter()->values()->all()
            );
        };

        $roles = [
            'super-admin' => 'Full access',
            'sales-executive' => 'Customers, quotations and draft invoices',
            'accounts' => 'Invoices, receipts and GST reports',
            'store-dispatch' => 'Stock, serials and invoice issue',
            'purchase' => 'Suppliers, products and masters',
            'viewer' => 'Read-only access',
        ];

        foreach ($roles as $slug => $description) {
            Role::updateOrCreate(
                ['slug' => $slug],
                ['name' => ucwords(str_replace('-', ' ', $slug)), 'description' => $description]
            );
        }

        $super = Role::where('slug', 'super-admin')->firstOrFail();
        $super->permissions()->sync($permissions->pluck('id')->all());

        $sync(Role::where('slug', 'sales-executive')->firstOrFail(), [
            'dashboard' => ['view'],
            'customers' => ['view', 'create', 'edit', 'print'],
            'quotations' => ['view', 'create', 'edit', 'print'],
            'products' => ['view'],
            'invoices' => ['view', 'create', 'edit', 'print'],
            'communication' => ['view', 'create', 'print'],
            'reports' => ['view'],
        ]);

        $sync(Role::where('slug', 'accounts')->firstOrFail(), [
            'dashboard' => ['view'],
            'customers' => ['view'],
            'invoices' => ['view', 'create', 'edit', 'approve', 'print', 'export'],
            'receipts' => ['view', 'create', 'edit', 'print'],
            'communication' => ['view', 'create', 'print'],
            'reports' => ['view', 'export', 'print'],
            'settings' => ['view'],
            'masters' => ['view'],
        ]);

        $sync(Role::where('slug', 'store-dispatch')->firstOrFail(), [
            'dashboard' => ['view'],
            'products' => ['view'],
            'stock' => ['view', 'create', 'edit', 'print'],
            'invoices' => ['view', 'print', 'approve'],
            'quotations' => ['view'],
        ]);

        $sync(Role::where('slug', 'purchase')->firstOrFail(), [
            'dashboard' => ['view'],
            'suppliers' => ['view', 'create', 'edit', 'delete'],
            'products' => ['view', 'create', 'edit'],
            'stock' => ['view'],
            'masters' => ['view', 'create', 'edit'],
        ]);

        $sync(Role::where('slug', 'viewer')->firstOrFail(), [
            'dashboard' => ['view'],
            'customers' => ['view', 'print'],
            'suppliers' => ['view', 'print'],
            'products' => ['view', 'print'],
            'quotations' => ['view', 'print'],
            'invoices' => ['view', 'print'],
            'stock' => ['view', 'print'],
            'receipts' => ['view', 'print'],
            'reports' => ['view', 'print'],
        ]);

        return $permissions;
    }

    private function seedAdmin(): User
    {
        $role = Role::where('slug', 'super-admin')->firstOrFail();

        return User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@jstmt.com',
                'password' => 'Admin@123',
                'role_id' => $role->id,
                'phone' => '0281-2560000',
                'is_active' => true,
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]
        );
    }

    private function seedCompany(State $gujarat): void
    {
        CompanyProfile::updateOrCreate(
            ['name' => 'JST Machine Tools'],
            [
                'trade_name' => 'JST Machine Tools',
                'address_line1' => 'Plot No. 12, GIDC Metoda',
                'address_line2' => 'Rajkot-Gondal Highway',
                'city' => 'Rajkot',
                'district' => 'Rajkot',
                'state_id' => $gujarat->id,
                'state_code' => '24',
                'pin' => '360021',
                'country' => 'India',
                'gstin' => '24AAAAA0000A1Z5',
                'pan' => 'AAAAA0000A',
                'phone' => '0281-2560000',
                'email' => 'accounts@jstmt.com',
                'website' => 'www.jstmt.com',
                'declaration' => 'We declare that this invoice shows the actual price of the goods described and that all particulars are true and correct.',
                'jurisdiction' => 'Rajkot',
                'footer_text' => 'Subject to Rajkot jurisdiction. E. & O.E.',
                'quotation_validity_days' => 7,
                'stock_valuation' => 'weighted_average',
                'prevent_negative_stock' => true,
                'credit_limit_hard_block' => false,
                'eway_bill_threshold' => 50000,
                'round_off_rule' => 'nearest',
            ]
        );

        BankAccount::updateOrCreate(
            ['account_number' => '12345678901'],
            [
                'account_holder' => 'JST Machine Tools',
                'bank_name' => 'State Bank of India',
                'branch' => 'Metoda, Rajkot',
                'ifsc' => 'SBIN0001234',
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }

    private function seedOperations(): Warehouse
    {
        $warehouse = Warehouse::updateOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main Store',
                'address' => 'GIDC Metoda, Rajkot, Gujarat 360021',
                'is_default' => true,
                'is_active' => true,
            ]
        );

        $today = Carbon::now();
        $startYear = $today->month >= 4 ? $today->year : $today->year - 1;
        FinancialYear::query()->update(['is_current' => false]);
        FinancialYear::updateOrCreate(
            ['name' => $startYear.'-'.($startYear + 1)],
            [
                'start_date' => $startYear.'-04-01',
                'end_date' => ($startYear + 1).'-03-31',
                'is_current' => true,
            ]
        );

        $series = [
            'quotation' => ['format_pattern' => '{MODELCODE}{SEQ}-{YEAR}', 'separator' => '-', 'zero_padding' => 3, 'reset_rule' => 'calendar_year'],
            'invoice' => ['format_pattern' => '{FY}/{SEQ}', 'separator' => '/', 'zero_padding' => 3, 'reset_rule' => 'financial_year'],
            'receipt' => ['format_pattern' => 'RCT/{FY}/{SEQ}', 'prefix' => 'RCT', 'separator' => '/', 'zero_padding' => 3, 'reset_rule' => 'financial_year'],
            'stock_adjustment' => ['format_pattern' => 'ADJ/{FY}/{SEQ}', 'prefix' => 'ADJ', 'separator' => '/', 'zero_padding' => 3, 'reset_rule' => 'financial_year'],
        ];

        foreach ($series as $type => $values) {
            NumberSeries::firstOrCreate(
                ['document_type' => $type],
                array_merge([
                    'prefix' => '',
                    'suffix' => '',
                    'separator' => '/',
                    'starting_number' => 1,
                    'current_number' => 0,
                    'zero_padding' => 0,
                    'reset_rule' => 'financial_year',
                ], $values)
            );
        }

        return $warehouse;
    }

    /**
     * @return array{tax: TaxRate, hsn: HsnSacCode, uom: Uom, term: PaymentTerm}
     */
    private function seedMasters(): array
    {
        $rates = [];
        foreach ([0, 5, 12, 18, 28] as $rate) {
            $rates[(string) $rate] = TaxRate::updateOrCreate(
                ['rate' => $rate],
                ['name' => 'GST '.$rate.'%', 'is_active' => true]
            );
        }

        foreach ([
            ['NOS', 'Numbers'],
            ['SET', 'Set'],
            ['KG', 'Kilogram'],
            ['MTR', 'Metre'],
        ] as [$code, $name]) {
            Uom::updateOrCreate(['code' => $code], ['name' => $name, 'is_active' => true]);
        }

        $hsn = HsnSacCode::updateOrCreate(
            ['code' => '84615011'],
            [
                'description' => 'Machine tools for sawing metal',
                'type' => 'HSN',
                'tax_rate_id' => $rates['18']->id,
                'is_active' => true,
            ]
        );
        $sac = HsnSacCode::updateOrCreate(
            ['code' => '998898'],
            [
                'description' => 'Other manufacturing services',
                'type' => 'SAC',
                'tax_rate_id' => $rates['18']->id,
                'is_active' => true,
            ]
        );

        $advance = PaymentTerm::updateOrCreate(
            ['name' => '100% Advance'],
            ['advance_percent' => 100, 'balance_percent' => 0, 'due_days' => 0, 'description' => 'Full payment before dispatch', 'is_active' => true]
        );
        $net30 = PaymentTerm::updateOrCreate(
            ['name' => 'Net 30'],
            ['advance_percent' => 0, 'balance_percent' => 100, 'due_days' => 30, 'description' => 'Payment due in 30 days', 'is_active' => true]
        );

        foreach (['NEFT', 'RTGS', 'UPI', 'Cheque', 'Cash'] as $mode) {
            PaymentMode::updateOrCreate(['name' => $mode], ['is_active' => true]);
        }

        ChargeType::updateOrCreate(
            ['code' => 'FREIGHT'],
            ['name' => 'Freight', 'hsn_sac_id' => $sac->id, 'tax_rate_id' => $rates['18']->id, 'is_taxable' => true, 'is_active' => true]
        );
        ChargeType::updateOrCreate(
            ['code' => 'PACKING'],
            ['name' => 'Packing', 'tax_rate_id' => $rates['18']->id, 'is_taxable' => true, 'is_active' => true]
        );
        ChargeType::updateOrCreate(
            ['code' => 'INSURANCE'],
            ['name' => 'Insurance', 'tax_rate_id' => $rates['18']->id, 'is_taxable' => true, 'is_active' => true]
        );

        TermsTemplate::updateOrCreate(
            ['name' => 'Standard machine quotation'],
            [
                'document_type' => 'quotation',
                'is_default' => true,
                'is_active' => true,
                'content' => implode("\n", [
                    'Payment: 100% Advance along with confirmed Purchase Order.',
                    'Prices: Ex-works Ahmedabad. Unloading at your site in your scope.',
                    'Delivery: Within 4 to 6 weeks from the date of technically and commercially clear order with advance payment.',
                    'Taxes: GST @ 18% extra as applicable at the time of dispatch.',
                    'Packing & Forwarding: Standard wooden packing included / extra at actuals.',
                    'Transport & Insurance: Transit insurance and freight to your account. Insurance approx 0.3% of invoice value.',
                    'Commissioning & Trial: Erection & commissioning engineer charges extra per day plus travel/boarding at actuals.',
                    'Warranty: 12 months from the date of commissioning or 15 months from the date of dispatch, whichever is earlier, against manufacturing defects.',
                    'Validity: This quotation is valid for 7 days from the date hereof.',
                ]),
            ]
        );

        return [
            'tax' => $rates['18'],
            'hsn' => $hsn,
            'uom' => Uom::where('code', 'NOS')->firstOrFail(),
            'term' => $net30,
            'advance' => $advance,
        ];
    }

    /**
     * @param  array{tax: TaxRate, hsn: HsnSacCode, uom: Uom, term: PaymentTerm}  $masters
     */
    private function seedSample(User $admin, State $maharashtra, Warehouse $warehouse, array $masters): void
    {
        $category = ProductCategory::updateOrCreate(
            ['slug' => 'cutting-machines'],
            ['name' => 'Cutting Machines', 'sort_order' => 1, 'is_active' => true]
        );
        $brand = Brand::updateOrCreate(['name' => 'JST'], ['is_active' => true]);

        $customer = Customer::updateOrCreate(
            ['customer_code' => 'CUST-0001'],
            [
                'legal_name' => 'Precision Engineering Works',
                'trade_name' => 'Precision Engineering Works',
                'gstin' => '27AABCP0000B1Z8',
                'pan' => 'AABCP0000B',
                'gst_registration_type' => 'regular',
                'contact_person' => 'Purchase Department',
                'mobile' => '020-25600000',
                'email' => 'purchase@precision-eng.example',
                'credit_limit' => 2000000,
                'payment_term_id' => $masters['term']->id,
                'opening_balance' => 0,
                'salesperson_id' => $admin->id,
                'notes' => 'Maharashtra customer for IGST demonstration.',
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $customer->addresses()->delete();
        foreach (['billing', 'shipping'] as $type) {
            $customer->addresses()->create([
                'type' => $type,
                'label' => ucfirst($type),
                'address_line1' => 'Plot 45, MIDC Bhosari',
                'address_line2' => 'Pune-Nashik Road',
                'city' => 'Pune',
                'district' => 'Pune',
                'state_id' => $maharashtra->id,
                'state_code' => '27',
                'pin' => '411026',
                'country' => 'India',
                'is_default' => true,
            ]);
        }

        $product = Product::updateOrCreate(
            ['sku' => 'JST-BS-500'],
            [
                'name' => 'Heavy Duty Band Saw / Miter Saw Machine',
                'model_number' => 'BS',
                'product_type' => 'goods',
                'goods_type' => 'machine',
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'hsn_sac_id' => $masters['hsn']->id,
                'tax_rate_id' => $masters['tax']->id,
                'uom_id' => $masters['uom']->id,
                'description' => 'Horizontal band saw / miter saw for metal sections, suitable for fabrication and machine shops.',
                'list_price' => 500000,
                'purchase_price' => 350000,
                'min_selling_price' => 450000,
                'default_discount_percent' => 0,
                'track_serial' => true,
                'reorder_level' => 1,
                'is_active' => true,
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
            ]
        );

        $product->specGroups()->delete();
        $capacity = $product->specGroups()->create(['name' => 'Cutting capacity', 'sort_order' => 0]);
        $capacity->items()->create(['label' => 'Round', 'value' => '250 mm', 'sort_order' => 0]);
        $capacity->items()->create(['label' => 'Square', 'value' => '200 x 200 mm', 'sort_order' => 1]);
        $drive = $product->specGroups()->create(['name' => 'Drive', 'sort_order' => 1]);
        $drive->items()->create(['label' => 'Motor', 'value' => '2 HP / 1440 RPM', 'sort_order' => 0]);
        $drive->items()->create(['label' => 'Blade', 'value' => '27 x 0.9 mm', 'sort_order' => 1]);

        $product->components()->delete();
        $product->components()->create(['component_name' => 'Main motor', 'brand_name' => 'Crompton', 'sort_order' => 0]);
        $product->components()->create(['component_name' => 'Gear box', 'brand_name' => 'JST', 'sort_order' => 1]);
        $product->components()->create(['component_name' => 'Coolant pump', 'brand_name' => 'Local', 'sort_order' => 2]);

        $serial = 'SR NO.1351-2627';
        if (! ProductSerial::withTrashed()->where('product_id', $product->id)->where('serial_number', $serial)->exists()) {
            app(StockService::class)->stockIn(
                $product->id,
                1,
                'opening',
                null,
                'OPENING',
                $serial,
                350000,
                $warehouse->id,
                'Opening stock',
                $admin->id,
            );
        }
    }
}
