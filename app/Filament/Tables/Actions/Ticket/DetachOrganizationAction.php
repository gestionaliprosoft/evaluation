<?php

namespace App\Filament\Tables\Actions\Ticket;

use App\Models\Organization;
use App\Models\Ticket\Ticket;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class DetachOrganizationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'detachOrganizationAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
            ->label(__('Detach From Organization'))
            ->visible(fn (Ticket $record) => $record->ticketable_type == Organization::class && auth()->user()->can('update', $record))
            ->action(function (Ticket $record) {
                $record->ticketable_id = null;
                $record->ticketable_type = null;
                $record->update();

                Notification::make()
                    ->title(__('ticket.Ticket Has Been Detached'))
                    ->success()
                    ->send();
            });
    }
}
