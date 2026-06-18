<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\Pages;

use App\Filament\Clusters\Projects\Resources\ProjectProjectResource;
use App\Traits\BaseListSettings;
use Filament\Resources\Pages\ListRecords;

class ListProjectProjects extends ListRecords
{
    use BaseListSettings;

    protected static string $resource = ProjectProjectResource::class;
}
