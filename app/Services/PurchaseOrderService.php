<?php

namespace App\Services;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use App\Models\Product\Tax;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderModel;
use App\Models\Purchase\PurchaseOrderStatus;
use App\Models\Purchase\PurchaseStockEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Arr;

class PurchaseOrderService
{
    /**
     * Calculate Totals
     */
    public function calculateTotals(
        ?string $quantity = null,
        ?string $price = null,
        ?string $discount = null,
        $discountIsPercentage = false,
        $taxes = null
    ): array {
        $taxes = $taxes ? $taxes : 0;
        $net = 0;
        $subtotal = 0;
        $totalDiscount = 0;
        $totalTaxes = 0;

        if ($quantity && $price) {
            $net = $quantity * $price;

            if ($discount && $discount !== 0) {
                if (! $discountIsPercentage) {
                    $subtotal = $net - $discount;
                    $totalDiscount = $discount;
                } else {
                    $totalDiscount = $net * $discount / 100;
                    $subtotal = $net - $totalDiscount;
                }
            } else {
                $subtotal = $net;
            }
        }

        $taxRecord = Tax::where('id', $taxes)->first();

        if (isset($taxRecord->percentage)) {
            if ($taxRecord->percentage) {
                $totalTaxes += $subtotal * $taxRecord->value / 100;
            } else {
                $totalTaxes += $taxRecord->value;
            }
        }

        return [
            'subtotalBeforeTax' => $net - $totalDiscount,
            'subtotal' => $subtotal + $totalTaxes,
            'totalDiscount' => (float) $totalDiscount,
            'totalTaxes' => $totalTaxes,
        ];
    }

    public function getPrice($productId = null, $orderModelId = null): array|ProductPrice|null
    {
        if (! $orderModelId || ! $productId) {
            return [];
        }

        $orderModel = PurchaseOrderModel::where('id', $orderModelId)->first();
        $priceListId = $orderModel->product_pricelist_id;

        return ProductPrice::where('product_id', $productId)
            ->where('product_pricelists_id', $priceListId)->first();
    }

    public function getDefaultStatus(): ?int
    {
        return PurchaseOrderStatus::where('is_default', true)
            ->pluck('id')
            ->first();
    }

    /**
     * Process the physical stock-in by parsing the JSON 'details' array field
     * inside the specific Purchase Order.
     *
     * @throws Exception
     */
    public function createStockEntryFromPurchaseOrder(PurchaseOrder $order): void
    {
        // 1. Retrieve and validate the JSON/array details containing the order lines
        $items = $order->details;

        if (empty($items) || ! is_array($items)) {
            throw new Exception(__('This purchase order does not contain any valid product lines in its details field.'));
        }

        // 2. Loop through each item tracked inside the JSON array document
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            $quantity = $item['quantity'] ?? 0;

            // Skip lines that don't reference a product or have zero/negative quantities
            if (! $productId || $quantity <= 0) {
                continue;
            }

            // 3. Fetch the product using Eloquent
            // Your model's TeamScope and MemberScope will automatically secure this query context
            $product = Product::find($productId);

            if (! $product) {
                throw new Exception(__('Product with ID :id was not found or is restricted by your tenant scope.', ['id' => $productId]));
            }

            // 4. Perform an atomic database increment to increase the stock safely
            // This updates the 'stock_quantity' column defined in your Product model
            $product->increment('stock_quantity', $quantity);

            // Log the operations for administrative audits and system tracing
            // Log::info("Inventory updated: Added {$quantity} units to Product ID {$product->id} via Purchase Order ID {$order->id}.");

            redirect(PurchaseOrderResource::getUrl('index'));
        }
    }

    /**
     * Genera e ritorna l'istanza PDF pronta per lo streaming o download.
     *
     * @param  $PurchaseStockEntry  $purchaseStockEntry
     * @param  array  $options  ['paper' => 'a4', 'orientation' => 'portrait', 'inline' => true|false]
     * @return \Barryvdh\DomPDF\PDF
     */
    public function makePdf(PurchaseStockEntry $purchaseStockEntry, array $options = [])
    {
        $data = $this->prepareData($purchaseStockEntry);

        $paper = $options['paper'] ?? 'a4';
        $orientation = $options['orientation'] ?? 'portrait';

        return Pdf::loadView('pdf.purchase_stock_entry_pdf', $data)
            ->setPaper($paper, $orientation);
    }

    /**
     * Prepara i dati per la view.
     */
    protected function prepareData(PurchaseStockEntry $purchaseStockEntry): array
    {
        // Eager load relationships
        $purchaseStockEntry->loadMissing(['team', 'purchaseOrder', 'attachments', 'user']);

        // normalize json rows
        $lines = $this->normalizeLinesFromJson($purchaseStockEntry->details);

        $totals = [
            'lines' => collect($lines)->sum(fn ($l) => ($l['quantity'] ?? 0)),
        ];

        return [
            'entry' => $purchaseStockEntry,
            'lines' => $lines,
            'totals' => $totals,
            'company' => [
                'name' => $purchaseStockEntry->team->business_name,
                'address' => $purchaseStockEntry->team->address.' '.$purchaseStockEntry->team->zip.' - '.$purchaseStockEntry->team->city.', '.$purchaseStockEntry->team->country,
            ],
            'generated_at' => now(),
        ];
    }

    /**
     * Default filename.
     */
    public function defaultFilename(PurchaseStockEntry $purchaseStockEntry): string
    {
        $documentDate = Carbon::parse($purchaseStockEntry->document_date)->format('d-m-Y');
        $date = $purchaseStockEntry->document_date ? $documentDate : now()->format('Ymd');

        return sprintf('distinta_nr_%s_%s.pdf', $purchaseStockEntry->id, $date);
    }

    /**
     * @param  string|array|null  $inputJson
     */
    public function normalizeLinesFromJson($inputJson): array
    {
        $rows = [];
        if (is_string($inputJson)) {
            $decoded = json_decode($inputJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return []; // invalid JSON
            }
            $rows = $decoded;
        } elseif (is_array($inputJson)) {
            $rows = $inputJson;
        } else {
            return [];
        }

        $normalized = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            // base fields
            $name = trim((string) Arr::get($row, 'name', ''));
            $description = trim((string) Arr::get($row, 'description', $name));
            $quantityRaw = Arr::get($row, 'quantity', 0);
            $productIdRaw = Arr::get($row, 'product_id', null);
            $internalCode = Arr::get($row, 'internal_code', null);
            $sku = Arr::get($row, 'sku', null);
            $unit = Arr::get($row, 'measurament_unit', Arr::get($row, 'measurement_unit', null));

            // normalizza quantità (accetta stringhe numeriche)
            $quantity = 0;
            if (is_numeric($quantityRaw)) {
                $quantity = (float) $quantityRaw;
            } elseif (is_string($quantityRaw)) {
                $quantity = (float) str_replace(',', '.', $quantityRaw);
            }

            // normalizza product_id
            $productId = null;
            if (is_numeric($productIdRaw)) {
                $productId = (int) $productIdRaw;
            } elseif (is_string($productIdRaw) && ctype_digit($productIdRaw)) {
                $productId = (int) $productIdRaw;
            }

            // costruisci la riga normalizzata
            $normalized[] = [
                'index' => $index,
                'name' => $name ?: $description ?: '—',
                'description' => $description ?: null,
                'quantity' => $quantity,
                'product_id' => $productId,
                'sku' => $sku,
                'internalCode' => $internalCode,
                'unit' => $unit ?: null,
            ];
        }

        return $normalized;
    }
}
