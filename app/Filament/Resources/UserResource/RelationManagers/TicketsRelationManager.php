<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Model;

class TicketsRelationManager extends AbstractRelationManager
{
    use \App\Traits\RelationManagers\TicketsRelationManager;

    protected static string $relationship = 'tickets';

    protected ?string $ticketableType = null;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', Ticket::class);
    }
}
