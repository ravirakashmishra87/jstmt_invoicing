<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\StockAdjustment;
use App\Models\StockLedger;
use App\Models\Warehouse;
use App\Services\DocumentNumberService;
use App\Services\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use RuntimeException;

class StockController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->with(['uom', 'stockBalances.warehouse'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('stock.index', compact('products', 'search'));
    }

    public function ledger(Request $request): View
    {
        $productId = $request->query('product_id');
        $from = $request->query('from');
        $to = $request->query('to');

        $entries = StockLedger::query()
            ->with(['product', 'warehouse'])
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->when($from, fn ($query) => $query->whereDate('transaction_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('transaction_date', '<=', $to))
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('stock.ledger', [
            'entries' => $entries,
            'products' => Product::orderBy('name')->get(['id', 'name', 'sku']),
            'productId' => $productId,
            'from' => $from,
            'to' => $to,
        ]);
    }

    public function serials(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $serials = ProductSerial::query()
            ->with(['product', 'warehouse', 'invoice'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('serial_number', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('stock.serials', compact('serials', 'search', 'status'));
    }

    public function opening(): View
    {
        return view('stock.opening', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeOpening(Request $request, StockService $stock): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'quantity' => ['nullable', 'numeric', 'min:0.0001'],
            'serial_numbers' => ['nullable', 'string'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:255'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $cost = isset($data['unit_cost']) ? (float) $data['unit_cost'] : (float) $product->purchase_price;
        $remarks = $data['remarks'] ?? 'Opening stock';

        try {
            DB::transaction(function () use ($product, $data, $stock, $cost, $remarks) {
                if ($product->track_serial) {
                    $serials = collect(preg_split('/\r\n|\r|\n/', (string) ($data['serial_numbers'] ?? '')))
                        ->map(fn ($line) => trim($line))
                        ->filter()
                        ->unique()
                        ->values();

                    if ($serials->isEmpty()) {
                        throw new RuntimeException('Enter one serial number per line for this product.');
                    }

                    foreach ($serials as $serial) {
                        $stock->stockIn(
                            $product->id,
                            1,
                            'opening',
                            null,
                            'OPENING',
                            $serial,
                            $cost,
                            $data['warehouse_id'] ?? null,
                            $remarks,
                            auth()->id(),
                        );
                    }
                } else {
                    if (empty($data['quantity'])) {
                        throw new RuntimeException('Quantity is required.');
                    }
                    $stock->stockIn(
                        $product->id,
                        (float) $data['quantity'],
                        'opening',
                        null,
                        'OPENING',
                        null,
                        $cost,
                        $data['warehouse_id'] ?? null,
                        $remarks,
                        auth()->id(),
                    );
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (QueryException) {
            return back()->withInput()->with('error', 'A serial number in that list already exists.');
        }

        return redirect()->route('stock.index')->with('success', 'Opening stock posted.');
    }

    public function adjustment(): View
    {
        return view('stock.adjustment', [
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'serials' => ProductSerial::where('status', 'in_stock')->orderBy('serial_number')->get(['id', 'product_id', 'serial_number']),
        ]);
    }

    public function storeAdjustment(Request $request, StockService $stock, DocumentNumberService $numbers): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'adjustment_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'product_id' => ['required', 'exists:products,id'],
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
            'product_serial_id' => ['nullable', 'exists:product_serials,id'],
            'serial_number' => ['nullable', 'string', 'max:100'],
        ]);

        $product = Product::findOrFail($data['product_id']);
        $qty = (float) $data['quantity'];
        $diff = $data['direction'] === 'in' ? $qty : -1 * $qty;

        try {
            DB::transaction(function () use ($data, $product, $stock, $numbers, $qty, $diff) {
                $adjustment = StockAdjustment::create([
                    'adjustment_number' => $numbers->next('stock_adjustment'),
                    'adjustment_date' => $data['adjustment_date'],
                    'warehouse_id' => $data['warehouse_id'],
                    'reason' => $data['reason'],
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'created_by' => auth()->id(),
                ]);

                $serialId = $data['product_serial_id'] ?? null;
                $serialNumber = trim((string) ($data['serial_number'] ?? ''));
                $serial = null;

                if ($product->track_serial && $diff < 0) {
                    $serial = ProductSerial::query()
                        ->where('id', $serialId)
                        ->where('product_id', $product->id)
                        ->where('status', 'in_stock')
                        ->lockForUpdate()
                        ->first();
                    if (! $serial) {
                        throw new RuntimeException('Select an in-stock serial to adjust out.');
                    }
                    $serialNumber = $serial->serial_number;
                    $qty = 1;
                    $diff = -1;
                }

                if ($product->track_serial && $diff > 0 && $serialNumber === '') {
                    throw new RuntimeException('Enter the serial number being received.');
                }

                $adjustment->items()->create([
                    'product_id' => $product->id,
                    'product_serial_id' => $serial?->id,
                    'quantity_diff' => $diff,
                    'remarks' => $data['reason'],
                ]);

                if ($diff > 0) {
                    $stock->stockIn(
                        $product->id,
                        $product->track_serial ? 1 : $qty,
                        'adjustment',
                        $adjustment->id,
                        $adjustment->adjustment_number,
                        $product->track_serial ? $serialNumber : null,
                        (float) $product->purchase_price,
                        (int) $data['warehouse_id'],
                        $data['reason'],
                        auth()->id(),
                    );
                } else {
                    $stock->stockOut(
                        $product->id,
                        $product->track_serial ? 1 : $qty,
                        'adjustment',
                        $adjustment->id,
                        $adjustment->adjustment_number,
                        $serial?->serial_number,
                        $serial?->id,
                        (int) $data['warehouse_id'],
                        $data['reason'],
                        auth()->id(),
                    );
                    if ($serial) {
                        $serial->update(['status' => 'scrapped']);
                    }
                }
            });
        } catch (RuntimeException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        } catch (QueryException) {
            return back()->withInput()->with('error', 'Could not post the adjustment. Check the serial number.');
        }

        return redirect()->route('stock.ledger')->with('success', 'Stock adjustment posted.');
    }
}
