<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\HsnSacCode;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\TaxRate;
use App\Models\Uom;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with(['category', 'brand', 'hsnSac', 'taxRate', 'uom', 'images'])
            ->withSum('stockBalances as qty_on_hand', 'quantity_on_hand')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('model_number', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact('products', 'search'));
    }

    public function create(): View
    {
        return view('products.create', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateProduct($request);

        $product = DB::transaction(function () use ($request, $data) {
            $product = Product::create([
                ...$data,
                'track_serial' => $request->boolean('track_serial'),
                'is_active' => $request->boolean('is_active'),
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);
            $this->syncSpecs($product, $request->input('spec_groups', []));
            $this->syncComponents($product, $request->input('components', []));
            $this->storePrimaryImage($product, $request);

            return $product;
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product '.$product->sku.' created.');
    }

    public function show(Product $product): View
    {
        $product->load([
            'category', 'brand', 'hsnSac', 'taxRate', 'uom', 'images',
            'specGroups.items', 'components',
            'serials' => fn ($query) => $query->latest('id')->limit(25),
            'stockBalances.warehouse',
        ]);

        return view('products.show', [
            'product' => $product,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function edit(Product $product): View
    {
        $product->load(['specGroups.items', 'components', 'images']);

        return view('products.edit', array_merge($this->formData(), compact('product')));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $this->validateProduct($request, $product);

        DB::transaction(function () use ($request, $product, $data) {
            $product->update([
                ...$data,
                'track_serial' => $request->boolean('track_serial'),
                'is_active' => $request->boolean('is_active'),
                'updated_by' => auth()->id(),
            ]);
            $this->syncSpecs($product, $request->input('spec_groups', []));
            $this->syncComponents($product, $request->input('components', []));
            if ($request->boolean('remove_image')) {
                $this->removePrimaryImage($product);
            } else {
                $this->storePrimaryImage($product, $request);
            }
        });

        return redirect()
            ->route('products.show', $product)
            ->with('success', 'Product updated.');
    }

    public function destroyImage(Product $product): RedirectResponse
    {
        $this->removePrimaryImage($product);

        return back()->with('success', 'Product image removed.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        try {
            $product->delete();
        } catch (QueryException) {
            return back()->with('error', 'This product cannot be deleted because it is used on documents.');
        }

        return redirect()->route('products.index')->with('success', 'Product archived.');
    }

    public function clone(Product $product): RedirectResponse
    {
        $copy = DB::transaction(function () use ($product) {
            $product->load(['specGroups.items', 'components']);
            $sku = $this->uniqueSku($product->sku.'-COPY');

            $copy = $product->replicate([
                'sku', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at',
            ]);
            $copy->sku = $sku;
            $copy->name = $product->name.' (Copy)';
            $copy->created_by = auth()->id();
            $copy->updated_by = auth()->id();
            $copy->save();

            foreach ($product->specGroups as $group) {
                $newGroup = $copy->specGroups()->create([
                    'name' => $group->name,
                    'sort_order' => $group->sort_order,
                ]);
                foreach ($group->items as $item) {
                    $newGroup->items()->create([
                        'label' => $item->label,
                        'value' => $item->value,
                        'sort_order' => $item->sort_order,
                    ]);
                }
            }

            foreach ($product->components as $component) {
                $copy->components()->create([
                    'component_name' => $component->component_name,
                    'brand_name' => $component->brand_name,
                    'sort_order' => $component->sort_order,
                ]);
            }

            return $copy;
        });

        return redirect()
            ->route('products.edit', $copy)
            ->with('success', 'Product cloned as '.$copy->sku.'. Stock and serials were not copied.');
    }

    public function storeSerial(Request $request, Product $product, StockService $stock): RedirectResponse
    {
        if (! $product->track_serial) {
            return back()->with('error', 'This product does not track serial numbers.');
        }

        $data = $request->validate([
            'serial_number' => ['required', 'string', 'max:100'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $stock->stockIn(
                $product->id,
                1,
                'serial_receipt',
                null,
                null,
                trim($data['serial_number']),
                isset($data['purchase_cost']) ? (float) $data['purchase_cost'] : (float) $product->purchase_price,
                $data['warehouse_id'] ?? null,
                $data['notes'] ?? 'Serial added from product card',
                auth()->id(),
            );
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (QueryException) {
            return back()->withInput()->with('error', 'That serial number already exists for this product.');
        }

        return back()->with('success', 'Serial '.$data['serial_number'].' added to stock.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $skuRule = 'required|string|max:100|unique:products,sku';
        if ($product) {
            $skuRule .= ','.$product->id;
        }

        $data = $request->validate([
            'sku' => $skuRule,
            'name' => ['required', 'string', 'max:255'],
            'model_number' => ['nullable', 'string', 'max:100'],
            'product_type' => ['required', 'in:goods,service'],
            'goods_type' => ['nullable', 'in:machine,spare,consumable,accessory'],
            'category_id' => ['nullable', 'exists:product_categories,id'],
            'brand_id' => ['nullable', 'exists:brands,id'],
            'hsn_sac_id' => ['nullable', 'exists:hsn_sac_codes,id'],
            'tax_rate_id' => ['nullable', 'exists:tax_rates,id'],
            'uom_id' => ['nullable', 'exists:uoms,id'],
            'description' => ['nullable', 'string'],
            'list_price' => ['required', 'numeric', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'min_selling_price' => ['nullable', 'numeric', 'min:0'],
            'default_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'spec_groups' => ['nullable', 'array'],
            'spec_groups.*.name' => ['nullable', 'string', 'max:255'],
            'spec_groups.*.items' => ['nullable', 'array'],
            'spec_groups.*.items.*.label' => ['nullable', 'string', 'max:255'],
            'spec_groups.*.items.*.value' => ['nullable', 'string'],
            'components' => ['nullable', 'array'],
            'components.*.component_name' => ['nullable', 'string', 'max:255'],
            'components.*.brand_name' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        $data['sku'] = strtoupper($data['sku']);
        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['default_discount_percent'] = $data['default_discount_percent'] ?? 0;
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['goods_type'] = $data['product_type'] === 'goods' ? ($data['goods_type'] ?? null) : null;

        unset($data['spec_groups'], $data['components'], $data['image'], $data['remove_image']);

        return $data;
    }

    private function storePrimaryImage(Product $product, Request $request): void
    {
        if (! $request->hasFile('image')) {
            return;
        }

        $file = $request->file('image');
        $directory = 'products/'.$product->id;
        $filename = 'primary_'.now()->format('YmdHis').'_'.Str::random(6).'.'.$file->getClientOriginalExtension();
        $path = $file->storeAs($directory, $filename, 'public');

        $existing = $product->images()->where('is_primary', true)->get();
        foreach ($existing as $image) {
            $image->delete();
        }

        $product->images()->create([
            'path' => $path,
            'caption' => $product->name,
            'is_primary' => true,
            'sort_order' => 0,
        ]);
    }

    private function removePrimaryImage(Product $product): void
    {
        $product->images()->where('is_primary', true)->get()->each->delete();

        if ($product->images()->count() === 0 && Storage::disk('public')->exists('products/'.$product->id)) {
            Storage::disk('public')->deleteDirectory('products/'.$product->id);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $groups
     */
    private function syncSpecs(Product $product, array $groups): void
    {
        $product->specGroups()->delete();
        $order = 0;

        foreach (array_values($groups) as $group) {
            if (! is_array($group)) {
                continue;
            }
            $name = trim((string) ($group['name'] ?? ''));
            $items = is_array($group['items'] ?? null) ? $group['items'] : [];
            $cleanItems = [];
            foreach (array_values($items) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $value = trim((string) ($item['value'] ?? ''));
                if ($value === '' && trim((string) ($item['label'] ?? '')) === '') {
                    continue;
                }
                $cleanItems[] = $item;
            }
            if ($name === '' && $cleanItems === []) {
                continue;
            }
            $specGroup = $product->specGroups()->create([
                'name' => $name !== '' ? $name : 'Specifications',
                'sort_order' => $order++,
            ]);
            $itemOrder = 0;
            foreach ($cleanItems as $item) {
                $specGroup->items()->create([
                    'label' => $item['label'] ?? null,
                    'value' => $item['value'] ?? '',
                    'sort_order' => $itemOrder++,
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $components
     */
    private function syncComponents(Product $product, array $components): void
    {
        $product->components()->delete();
        $order = 0;

        foreach (array_values($components) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['component_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $product->components()->create([
                'component_name' => $name,
                'brand_name' => $row['brand_name'] ?? null,
                'sort_order' => $order++,
            ]);
        }
    }

    private function uniqueSku(string $base): string
    {
        $sku = Str::upper(Str::limit($base, 90, ''));
        $candidate = $sku;
        $suffix = 2;
        while (Product::withTrashed()->where('sku', $candidate)->exists()) {
            $candidate = $sku.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => ProductCategory::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'hsnCodes' => HsnSacCode::where('is_active', true)->orderBy('code')->get(),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('code')->get(),
        ];
    }
}
