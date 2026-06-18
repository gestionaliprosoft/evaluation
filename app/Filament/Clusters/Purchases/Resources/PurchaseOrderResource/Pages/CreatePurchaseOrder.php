<?php

namespace App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\Pages;

use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource;
use App\Models\Purchase\PurchaseOrder;
use App\Services\ModuleSettingService;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreatePurchaseOrder extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = PurchaseOrderResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $moduleSettingService = app(ModuleSettingService::class);

        $data['team_id'] = Arr::has($data, 'team_id') ? $data['team_id'] : auth()->user()->team_id;
        $data['uuid'] = Str::uuid();
        $data['number_seq'] = PurchaseOrder::where('team_id', $data['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
        $data['number'] = $moduleSettingService->getModuleSettings('Orders', 'number').$data['number_seq'];

        return $data;
    }
}
