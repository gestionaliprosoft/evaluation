<?php

namespace App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\Pages;

use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreatePaymentReceipt extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = PaymentReceiptResource::class;

    protected function mutateFormdataBeforeCreate(array $data): array
    {
        $data['uuid'] = Str::uuid();

        return $data;
    }
}
