<?php

namespace App\Services;

use App\Models\Product\ProductPrice;
use App\Models\Product\Tax;
use App\Models\Sale\SaleQuoteModel;
use App\Models\Sale\SaleQuoteStatus;

class QuoteService
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

    public function getPrice($productId = null, $quoteModelId = null): array|ProductPrice|null
    {
        if (! $quoteModelId || ! $productId) {
            return [];
        }

        $quoteModel = SaleQuoteModel::where('id', $quoteModelId)->first();
        $priceListId = $quoteModel->product_pricelist_id;

        return ProductPrice::where('product_id', $productId)
            ->where('product_pricelists_id', $priceListId)->first();
    }

    public function getDefaultStatus(): ?int
    {
        return SaleQuoteStatus::where('team_id', auth()->user()->team_id)
            ->where('is_default', true)
            ->value('id');
    }
}
