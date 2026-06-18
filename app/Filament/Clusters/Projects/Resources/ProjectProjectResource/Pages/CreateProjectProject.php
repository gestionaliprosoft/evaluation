<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\Pages;

use App\Filament\Clusters\Projects\Resources\ProjectProjectResource;
use App\Models\Project\ProjectProject;
use App\Services\ModuleSettingService;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class CreateProjectProject extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = ProjectProjectResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $moduleSettingService = app(ModuleSettingService::class);

        $data['team_id'] = Arr::has($data, 'team_id') ? $data['team_id'] : auth()->user()->team_id;
        $data['uuid'] = Str::uuid();
        $data['number_seq'] = ProjectProject::where('team_id', $data['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
        $data['number'] = $moduleSettingService->getModuleSettings('ProjectProjects', 'number').$data['number_seq'];

        return $data;
    }
}
