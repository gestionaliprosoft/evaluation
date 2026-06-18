<?php

namespace App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\Pages;

use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource;
use App\Traits\BaseViewSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewPaymentReceipt extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;

    protected static string $resource = PaymentReceiptResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->uuid;
    }
}
