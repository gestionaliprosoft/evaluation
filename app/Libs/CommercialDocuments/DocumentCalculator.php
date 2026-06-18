<?php

namespace App\Libs\CommercialDocuments;

use App\Models\Accounting\PaymentMethod;
use App\Models\Attachment;
use App\Models\Product\Tax;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DocumentCalculator
{
    public Model $record;

    public string $type;

    public function __construct($record, $type)
    {
        $this->record = $record;
        $this->type = $type;
    }

    public function calculate(): DocumentInvoice
    {
        $items = collect();
        $taxableAmounts = [];
        $totalDiscounts = 0;
        $totalTaxes = [];

        $partySeller = new PartySeller($this->type, $this->record);
        $seller = $partySeller->createSeller();

        $buyerCustomer = new BuyerCustomer($this->type, $this->record);
        $customer = $buyerCustomer->createCustomer();

        foreach ($this->record->details as $detail) {
            $recordTax = (isset($detail['taxes'])) ? Tax::where('id', $detail['taxes'])->first() : null;

            if (array_key_exists('taxes', $detail)) {
                ! isset($taxableAmounts[$recordTax?->name]) ? $taxableAmounts[$recordTax?->name] = 0 : null;
                ! isset($totalTaxes[$recordTax?->name]) ? $totalTaxes[$recordTax?->name] = 0 : null;

                $taxableAmounts[$recordTax?->name] += $detail['quantity'] * $detail['price'] - $detail['total_discount'];
                $totalTaxes[$recordTax?->name] += $detail['total_taxes'];
            }

            $items->push($this->addInvoiceItem($detail, $recordTax));

            $totalDiscounts += $detail['total_discount'];
        }

        $items = $items->isNotEmpty() ? $items : $items->push($this->addFakeInvoiceItem());

        // get attachment (logo)
        $filename = null;
        if ($this->record->defaultModel) {
            $filename = Attachment::where('attachable_id', $this->record->defaultModel->getKey())
                ->where('attachable_type', $this->record->defaultModel::class)->first();

            $filename = file_exists(storage_path('team-'.$filename?->team_id.'/'.Str::afterLast($this->record->defaultModel::class, '\\').'/'.$filename?->filename))
                ? storage_path('team-'.$filename?->team_id.'/'.Str::afterLast($this->record->defaultModel::class, '\\').'/'.$filename?->filename)
                : null;
        }

        return DocumentInvoice::make()
            ->template($this->record->defaultModel?->template == '' ? 'default' : $this->record->defaultModel->template)
            ->logo($filename ?? public_path('vendor/invoices/sample-logo.png'))
            ->documentName($this->record->defaultModel?->document_name ?? 'Document')
            ->filename($this->generateFilename())
            ->date(Carbon::parse($this->record?->date ?? Carbon::parse(now())))
            ->number($this->record?->number ?? '')
            ->seller($seller)
            ->buyer($customer)
            // ->shipping(1.99)
            ->addItems($items)
            ->totalDiscounts($totalDiscounts)
            ->totalTaxesSummary($totalTaxes)
            ->taxableAmounts($taxableAmounts)
            ->totalAmount($this->record?->total ?? 0)
            ->description($this->record?->description ?? '')
            ->notes($this->record->defaultModel?->annotations ?? '')
            ->terms($this->record?->terms ?? '')
            ->payment($this->generatePaymentMethod());
    }

    protected function addInvoiceItem($detail, $recordTax)
    {
        return DocumentInvoiceItem::make(__('cod: '.$detail['product_id']))
            ->sku($detail['sku'] ?? '')
            ->internalCode($detail['internal_code'])
            ->name($detail['name'])
            ->description($detail['description'])
            ->measuramentUnit($detail['measurament_unit'])
            ->quantity($detail['quantity'])
            ->price($detail['price'])
            ->discount($detail['discount'])
            ->isDiscountpercentage($detail['is_discount_percentage'] ?? false)
            ->totalDiscount($detail['total_discount'])
            ->taxes($recordTax?->getKey() ?? 0)
            ->totalTaxes($detail['total_taxes'])
            ->subtotal($detail['subtotal']);
    }

    protected function addFakeInvoiceItem()
    {
        $taxesArray = [];
        $taxesArray['name'] = __('Tax Name');
        $taxesArray['value'] = 10;

        return DocumentInvoiceItem::make(__('cod: 01'))
            ->sku('SKU-ABC-123')
            ->internalCode('ABC-123')
            ->name(__('Product name'))
            ->description(__('Description'))
            ->measuramentUnit('N.')
            ->quantity(1)
            ->price(100)
            ->discount(5)
            ->isDiscountpercentage(true)
            ->totalDiscount(5)
            ->taxes(10)
            ->totalTaxes(10)
            ->subtotal(95);
    }

    protected function generateFilename(): string
    {
        $documentModelName = $this->record->defaultModel?->document_name ?? 'Document';

        $recipient = match ($this->type) {
            'sale' => Str::limit($this->record?->organization->name, 15, ''),
            'quote' => Str::limit($this->record?->organization->name, 15, ''),
            'purchase' => Str::limit($this->record?->vendor->name, 15, ''),
        };

        $date = $this->record?->date ?? now()->format('Y-m-d');

        $number = $this->record?->number;

        return $documentModelName.'-'.$number.'-'.$date.'-'.$recipient.'-';
    }

    protected function generatePaymentMethod()
    {
        if ($this->record->payment_method_id) {
            $paymentMethod = PaymentMethod::find($this->record->payment_method_id);

            return $paymentMethod->description.'<br />'.$paymentMethod->details;
        }
    }
}
