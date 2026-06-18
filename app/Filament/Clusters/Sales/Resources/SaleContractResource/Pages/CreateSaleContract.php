<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Models\Sale\SaleContract;
use App\Services\ModuleSettingService;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateSaleContract extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = SaleContractResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $moduleSettingService = app(ModuleSettingService::class);

        $data['team_id'] = Arr::has($data, 'team_id') ? $data['team_id'] : auth()->user()->team_id;
        $data['uuid'] = Str::uuid();
        $data['number_seq'] = SaleContract::where('team_id', $data['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
        $data['number'] = $moduleSettingService->getModuleSettings('SaleContracts', 'number').$data['number_seq'];

        return $data;
    }
}
