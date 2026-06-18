<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketResource;
use App\Filament\Tables\HeaderActions\CloseAction;
use App\Traits\BaseViewSettings;
use App\Traits\Commentables\HasCommentableActions;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    use BaseViewSettings;
    use CommonSettings;
    use HasCommentableActions;

    protected static string $resource = TicketResource::class;

    protected function getJollyField()
    {
        return $this->record->title;
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
