<?php

namespace App\Filament\Tables\Actions\Ticket;

use App\Models\Organization;
use App\Models\Ticket\Ticket;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class AttachOrganizationAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'attachOrganizationAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
            ->label(__('Attach To Organization'))
            ->form([
                Select::make('organization_id')
                    ->label(__('resources.OrganizationResource'))
                    ->options(Organization::getOptionsForSelect())
                    ->searchable()
                    ->required(),
            ])
            ->visible(fn (Ticket $record) => ! $record->ticketable_id && auth()->user()->can('update', $record))
            ->action(function (array $data, Ticket $record) {
                $record->ticketable_id = $data['organization_id'];
                $record->ticketable_type = Organization::class;
                $record->update();

                Notification::make()
                    ->title(__('ticket.Ticket Has Been Associated'))
                    ->success()
                    ->send();
            });
    }
}
