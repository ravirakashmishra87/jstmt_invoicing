<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\ChargeType;
use App\Models\HsnSacCode;
use App\Models\PaymentMode;
use App\Models\PaymentTerm;
use App\Models\ProductCategory;
use App\Models\TaxRate;
use App\Models\Transporter;
use App\Models\Uom;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MasterController extends Controller
{
    public function index(): View
    {
        return view('masters.index', [
            'types' => $this->catalog(),
        ]);
    }

    public function list(Request $request, string $type): View
    {
        $meta = $this->meta($type);
        /** @var class-string<Model> $model */
        $model = $meta['model'];
        $edit = null;
        $view = null;
        if ($request->filled('edit')) {
            $edit = $model::query()->find($request->query('edit'));
        } elseif ($request->filled('view')) {
            $view = $model::query()->find($request->query('view'));
            if ($view && $type === 'categories') {
                $view->loadMissing('parent');
            }
            if ($view && $type === 'hsn') {
                $view->loadMissing('taxRate');
            }
        }

        $query = $model::query()->latest('id');
        if ($type === 'categories') {
            $query->with('parent');
        }

        return view('masters.list', [
            'type' => $type,
            'meta' => $meta,
            'records' => $query->paginate(20)->withQueryString(),
            'edit' => $edit,
            'view' => $view,
            'taxRates' => TaxRate::orderBy('rate')->get(),
            'hsnCodes' => HsnSacCode::orderBy('code')->get(),
            'categories' => ProductCategory::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, string $type): RedirectResponse
    {
        $meta = $this->meta($type);
        /** @var class-string<Model> $model */
        $model = $meta['model'];
        $data = $this->payload($request, $type, null);
        $model::create($data);

        return redirect()->route('masters.list', $type)->with('success', $meta['title'].' saved.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $meta = $this->meta($type);
        /** @var class-string<Model> $model */
        $model = $meta['model'];
        $record = $model::query()->findOrFail($id);
        $record->update($this->payload($request, $type, $id));

        return redirect()->route('masters.list', $type)->with('success', $meta['title'].' updated.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $meta = $this->meta($type);
        /** @var class-string<Model> $model */
        $model = $meta['model'];
        $record = $model::query()->findOrFail($id);

        try {
            $record->delete();
        } catch (QueryException) {
            return back()->with('error', 'This record is in use and cannot be deleted.');
        }

        return redirect()->route('masters.list', $type)->with('success', $meta['title'].' deleted.');
    }

    /**
     * @return array<string, array{title: string, hint: string, icon: string}>
     */
    private function catalog(): array
    {
        $items = [];
        foreach ($this->definitions() as $key => $meta) {
            $items[$key] = [
                'title' => $meta['title'],
                'hint' => $meta['hint'],
                'icon' => $meta['icon'],
            ];
        }

        return $items;
    }

    /**
     * @return array{model: class-string<Model>, title: string, hint: string, icon: string}
     */
    private function meta(string $type): array
    {
        $definitions = $this->definitions();
        abort_unless(isset($definitions[$type]), 404);

        return $definitions[$type];
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, string $type, ?int $id): array
    {
        if ($request->has('code')) {
            $request->merge(['code' => strtoupper((string) $request->input('code'))]);
        }
        if ($request->has('gstin')) {
            $request->merge(['gstin' => strtoupper((string) $request->input('gstin'))]);
        }

        $data = $request->validate($this->rules($type, $id));

        $rules = $this->rules($type, $id);
        if (array_key_exists('is_active', $rules)) {
            $data['is_active'] = $request->boolean('is_active');
        }
        if (array_key_exists('is_taxable', $rules)) {
            $data['is_taxable'] = $request->has('is_taxable') ? $request->boolean('is_taxable') : true;
        }
        foreach (['advance_percent', 'due_days', 'sort_order'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === null) {
                $data[$key] = 0;
            }
        }
        if (array_key_exists('balance_percent', $data) && $data['balance_percent'] === null) {
            $data['balance_percent'] = 100;
        }

        foreach (['tax_rate_id', 'hsn_sac_id', 'parent_id'] as $nullable) {
            if (array_key_exists($nullable, $data) && $data[$nullable] === '') {
                $data[$nullable] = null;
            }
        }

        if ($type === 'categories') {
            $data['slug'] = Str::slug($data['name']).($id ? '' : '-'.Str::lower(Str::random(4)));
            if ($id) {
                $existing = ProductCategory::find($id);
                $data['slug'] = $existing?->slug ?: $data['slug'];
            }
            $data['sort_order'] = $data['sort_order'] ?? 0;
        }

        if ($type === 'uoms' || $type === 'charge-types') {
            $data['code'] = strtoupper($data['code']);
        }
        if ($type === 'hsn') {
            $data['code'] = strtoupper($data['code']);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(string $type, ?int $id): array
    {
        $unique = function (string $table, string $column) use ($id): string {
            $rule = 'unique:'.$table.','.$column;
            if ($id) {
                $rule .= ','.$id;
            }

            return $rule;
        };

        return match ($type) {
            'uoms' => [
                'name' => ['required', 'string', 'max:100'],
                'code' => ['required', 'string', 'max:20', $unique('uoms', 'code')],
                'is_active' => ['nullable', 'boolean'],
            ],
            'tax-rates' => [
                'name' => ['required', 'string', 'max:100'],
                'rate' => ['required', 'numeric', 'min:0', 'max:100'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'hsn' => [
                'code' => ['required', 'string', 'max:20', $unique('hsn_sac_codes', 'code')],
                'description' => ['nullable', 'string', 'max:255'],
                'type' => ['required', 'in:HSN,SAC'],
                'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'payment-terms' => [
                'name' => ['required', 'string', 'max:255'],
                'advance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'balance_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
                'due_days' => ['nullable', 'integer', 'min:0'],
                'milestone' => ['nullable', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'charge-types' => [
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:30', $unique('charge_types', 'code')],
                'hsn_sac_id' => ['nullable', 'exists:hsn_sac_codes,id'],
                'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
                'is_taxable' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'transporters' => [
                'name' => ['required', 'string', 'max:255'],
                'gstin' => ['nullable', 'string', 'size:15'],
                'transporter_id' => ['nullable', 'string', 'max:50'],
                'phone' => ['nullable', 'string', 'max:30'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'categories' => [
                'name' => ['required', 'string', 'max:255'],
                'parent_id' => ['nullable', 'exists:product_categories,id'],
                'sort_order' => ['nullable', 'integer', 'min:0'],
                'is_active' => ['nullable', 'boolean'],
            ],
            'brands' => [
                'name' => ['required', 'string', 'max:255', $unique('brands', 'name')],
                'is_active' => ['nullable', 'boolean'],
            ],
            'payment-modes' => [
                'name' => ['required', 'string', 'max:100', $unique('payment_modes', 'name')],
                'is_active' => ['nullable', 'boolean'],
            ],
            default => abort(404),
        };
    }

    /**
     * @return array<string, array{model: class-string<Model>, title: string, hint: string, icon: string}>
     */
    private function definitions(): array
    {
        return [
            'uoms' => ['model' => Uom::class, 'title' => 'Units of Measure', 'hint' => 'NOS, SET, KG, MTR', 'icon' => 'rulers'],
            'tax-rates' => ['model' => TaxRate::class, 'title' => 'Tax Rates', 'hint' => 'GST slabs', 'icon' => 'percent'],
            'hsn' => ['model' => HsnSacCode::class, 'title' => 'HSN / SAC', 'hint' => 'Goods and service codes', 'icon' => 'upc'],
            'payment-terms' => ['model' => PaymentTerm::class, 'title' => 'Payment Terms', 'hint' => 'Advance and credit days', 'icon' => 'calendar2-check'],
            'charge-types' => ['model' => ChargeType::class, 'title' => 'Charge Types', 'hint' => 'Freight, packing, insurance', 'icon' => 'truck'],
            'transporters' => ['model' => Transporter::class, 'title' => 'Transporters', 'hint' => 'Dispatch carriers', 'icon' => 'truck-front'],
            'categories' => ['model' => ProductCategory::class, 'title' => 'Categories', 'hint' => 'Product groups', 'icon' => 'diagram-3'],
            'brands' => ['model' => Brand::class, 'title' => 'Brands', 'hint' => 'Machine and component brands', 'icon' => 'award'],
            'payment-modes' => ['model' => PaymentMode::class, 'title' => 'Payment Modes', 'hint' => 'NEFT, RTGS, UPI, cash', 'icon' => 'wallet2'],
        ];
    }
}
