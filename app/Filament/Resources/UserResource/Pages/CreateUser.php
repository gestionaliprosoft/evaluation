<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Services\TeamService;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! Arr::hasAll($data, ['team_id'])) {
            $data['team_id'] = auth()->user()->team_id;
        }

        // Resolve the service from the container
        $teamService = app(TeamService::class);

        $tenantId = $teamService->getTenantFromTeam($data['team_id']);
        $data['tenant_id'] = $tenantId;

        return $data;
    }
}
