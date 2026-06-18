<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketPackageResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Ticket\TicketIntervention;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TicketInterventionRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\TicketInterventionRelationManager;

    protected static string $relationship = 'ticketInterventions';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return TicketIntervention::whereHas('ticketPackages', fn (Builder $query) => $query->whereTicketPackageId($ownerRecord->getKey()))->count();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', TicketIntervention::class);
    }
}
