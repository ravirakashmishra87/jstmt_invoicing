<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\StockBalance;
use App\Models\StockLedger;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class StockService
{
    public function defaultWarehouseId(): int
    {
        $warehouse = Warehouse::where('is_default', true)->first()
            ?? Warehouse::first();

        if (! $warehouse) {
            throw new RuntimeException('No warehouse configured.');
        }

        return (int) $warehouse->id;
    }

    public function stockIn(
        int $productId,
        float $qty,
        string $documentType,
        ?int $documentId = null,
        ?string $documentNumber = null,
        ?string $serialNumber = null,
        ?float $unitCost = null,
        ?int $warehouseId = null,
        ?string $remarks = null,
        ?int $userId = null,
    ): void {
        $this->move($productId, $qty, 0, $documentType, $documentId, $documentNumber, $serialNumber, $unitCost, $warehouseId, $remarks, $userId);
    }

    public function stockOut(
        int $productId,
        float $qty,
        string $documentType,
        ?int $documentId = null,
        ?string $documentNumber = null,
        ?string $serialNumber = null,
        ?int $serialId = null,
        ?int $warehouseId = null,
        ?string $remarks = null,
        ?int $userId = null,
    ): void {
        $this->move($productId, 0, $qty, $documentType, $documentId, $documentNumber, $serialNumber, null, $warehouseId, $remarks, $userId, $serialId);
    }

    private function move(
        int $productId,
        float $qtyIn,
        float $qtyOut,
        string $documentType,
        ?int $documentId,
        ?string $documentNumber,
        ?string $serialNumber,
        ?float $unitCost,
        ?int $warehouseId,
        ?string $remarks,
        ?int $userId,
        ?int $serialId = null,
    ): void {
        DB::transaction(function () use ($productId, $qtyIn, $qtyOut, $documentType, $documentId, $documentNumber, $serialNumber, $unitCost, $warehouseId, $remarks, $userId, $serialId) {
            $warehouseId = $warehouseId ?: $this->defaultWarehouseId();
            $product = Product::lockForUpdate()->findOrFail($productId);

            $balance = StockBalance::firstOrCreate(
                ['product_id' => $productId, 'warehouse_id' => $warehouseId],
                ['quantity_on_hand' => 0, 'quantity_reserved' => 0, 'average_cost' => 0]
            );
            $balance = StockBalance::where('id', $balance->id)->lockForUpdate()->first();

            $newQty = (float) $balance->quantity_on_hand + $qtyIn - $qtyOut;
            $preventNegative = (bool) (optional(\App\Models\CompanyProfile::first())->prevent_negative_stock ?? true);
            if ($preventNegative && $newQty < -0.0001) {
                throw new RuntimeException("Insufficient stock for {$product->name}.");
            }

            if ($qtyIn > 0 && $unitCost !== null) {
                $currentValue = (float) $balance->quantity_on_hand * (float) $balance->average_cost;
                $addValue = $qtyIn * $unitCost;
                $balance->average_cost = $newQty > 0 ? round(($currentValue + $addValue) / $newQty, 4) : $unitCost;
            }

            $balance->quantity_on_hand = $newQty;
            $balance->save();

            if ($serialNumber && $product->track_serial) {
                if ($qtyIn > 0) {
                    ProductSerial::create([
                        'product_id' => $productId,
                        'serial_number' => $serialNumber,
                        'warehouse_id' => $warehouseId,
                        'status' => 'in_stock',
                        'purchase_cost' => $unitCost,
                    ]);
                }
                if ($qtyOut > 0 && $serialId) {
                    ProductSerial::where('id', $serialId)->update(['status' => 'sold']);
                }
            }

            StockLedger::create([
                'transaction_date' => now()->toDateString(),
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'product_serial_id' => $serialId,
                'serial_number' => $serialNumber,
                'qty_in' => $qtyIn,
                'qty_out' => $qtyOut,
                'balance_qty' => $newQty,
                'unit_cost' => $unitCost,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'document_number' => $documentNumber,
                'remarks' => $remarks,
                'created_by' => $userId,
            ]);
        });
    }

    public function reverseDocument(string $documentType, int $documentId, ?int $userId = null): void
    {
        DB::transaction(function () use ($documentType, $documentId, $userId) {
            $rows = StockLedger::where('document_type', $documentType)
                ->where('document_id', $documentId)
                ->orderBy('id')
                ->get();

            foreach ($rows as $row) {
                if ((float) $row->qty_out > 0) {
                    $this->stockIn(
                        $row->product_id,
                        (float) $row->qty_out,
                        $documentType.'_reversal',
                        $documentId,
                        $row->document_number,
                        $row->serial_number,
                        $row->unit_cost ? (float) $row->unit_cost : null,
                        $row->warehouse_id,
                        'Reversal of '.$row->document_number,
                        $userId
                    );
                    if ($row->product_serial_id) {
                        ProductSerial::where('id', $row->product_serial_id)->update([
                            'status' => 'in_stock',
                            'invoice_id' => null,
                        ]);
                    }
                }
                if ((float) $row->qty_in > 0) {
                    $this->stockOut(
                        $row->product_id,
                        (float) $row->qty_in,
                        $documentType.'_reversal',
                        $documentId,
                        $row->document_number,
                        $row->serial_number,
                        $row->product_serial_id,
                        $row->warehouse_id,
                        'Reversal of '.$row->document_number,
                        $userId
                    );
                }
            }
        });
    }
}
