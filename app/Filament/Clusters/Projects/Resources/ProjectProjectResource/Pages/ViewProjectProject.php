<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\Pages;

use App\Filament\Clusters\Projects\Resources\ProjectProjectResource;
use App\Filament\Tables\HeaderActions\CloseAction;
use App\Traits\BaseViewSettings;
use App\Traits\Commentables\HasCommentableActions;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewProjectProject extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;
    use HasCommentableActions;

    protected static string $resource = ProjectProjectResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            static::editAction(),
            static::commentableHeaderAction(),
            CloseAction::make('close'),
        ];
    }
}
