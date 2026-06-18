<?php

namespace App\Libs\CommercialDocuments;

/**
 * Class InvoiceItem
 */
class DocumentInvoiceItem
{
    /**
     * @var int
     */
    public $product_id;

    /**
     * @var string
     */
    public $internal_code;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description;

    /**
     * @var string
     */
    public $measurament_unit;

    /**
     * @var string
     */
    public $sku;

    /**
     * @var float
     */
    public $quantity;

    /**
     * @var float
     */
    public $price;

    /**
     * @var float
     */
    public $discount;

    /**
     * @var float
     */
    public $total_discount;

    /**
     * @var bool
     */
    public $is_discount_percentage;

    /**
     * @var int
     */
    public $taxes;

    /**
     * @var float
     */
    public $total_taxes;

    /**
     * @var float
     */
    public $subtotal;

    /**
     * InvoiceItem constructor.
     */
    public function __construct()
    {
        //
    }

    public static function make($product)
    {
        return (new self)->product($product);
    }

    /**
     * @return $this
     */
    public function productId(int $productId)
    {
        $this->product_id = $productId;

        return $this;
    }

    /**
     * @param  int  $product
     * @return $this
     */
    public function product(string $product)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * @return $this
     */
    public function internalCode(?string $internalCode = '')
    {
        $this->internal_code = $internalCode;

        return $this;
    }

    /**
     * @return $this
     */
    public function sku(?string $sku = '')
    {
        $this->sku = $sku;

        return $this;
    }

    /**
     * @return $this
     */
    public function name(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return $this
     */
    public function description(string $description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return $this
     */
    public function measuramentUnit(?string $measuramentUnit)
    {
        $this->measurament_unit = $measuramentUnit ?? '';

        return $this;
    }

    /**
     * @return $this
     */
    public function quantity(float $quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * @return $this
     */
    public function price(float $price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return $this
     */
    public function discount(?float $discount = 0)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * @return $this
     */
    public function totalDiscount(float $totalDiscount)
    {
        $this->total_discount = $totalDiscount;

        return $this;
    }

    /**
     * @return $this
     */
    public function isDiscountPercentage(bool $isDiscountPercentage)
    {
        $this->is_discount_percentage = $isDiscountPercentage;

        return $this;
    }

    /**
     * @return $this
     */
    public function taxes(int $taxes)
    {
        $this->taxes = $taxes;

        return $this;
    }

    /**
     * @return $this
     */
    public function totalTaxes(float $totalTaxes)
    {
        $this->total_taxes = $totalTaxes;

        return $this;
    }

    /**
     * @return $this
     */
    public function subTotal(float $subtotal)
    {
        $this->subtotal = $subtotal;

        return $this;
    }
}
