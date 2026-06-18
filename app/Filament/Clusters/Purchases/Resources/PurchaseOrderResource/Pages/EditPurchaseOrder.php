<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use App\Libs\GenerateService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = PurchaseOrderResource::class;

    protected function getJollyField()
    {
        return 'Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            GenerateService::generateCommercialPdf('PurchaseOrder', 'purchase', true),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = self::setTeamIdFromUserId($data);

        if (! $data['details']) {
            $data['total'] = 0;
        }

        return $data;
    }
}
