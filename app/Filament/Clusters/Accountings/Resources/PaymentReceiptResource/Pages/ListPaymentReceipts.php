<?php

namespace App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\Pages;

use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListPaymentReceipts extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = PaymentReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [
            static::createAction(),
        ];
    }
}
