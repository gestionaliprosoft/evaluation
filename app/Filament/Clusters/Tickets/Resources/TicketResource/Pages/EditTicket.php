<?php

namespace App\Filament\Clusters\Tickets\Resources\TicketResource\Pages;

use App\Filament\Clusters\Tickets\Resources\TicketResource;
use App\Models\Organization;
use App\Models\Ticket\Ticket;
use App\Traits\BaseSettings;
use App\Traits\Commentables\HasCommentableActions;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

/**
 * Summary of EditTicket
 */
class EditTicket extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;
    use HasCommentableActions;

    protected static string $resource = TicketResource::class;

    protected function getJollyField()
    {
        return $this->record->title;
    }

    protected function getHeaderActions(): array
    {
        return [
            static::commentableHeaderAction(),
            Actions\Action::make('Attach Organization')
                ->requiresConfirmation()
                ->label(__('Attach To Organization'))
                ->form([
                    Forms\Components\Select::make('organization_id')
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
                })
                ->successRedirectUrl(fn (Ticket $record): string => route('filament.admin.resources.tickets.edit', [
                    'record' => $record,
                ])),
            Actions\Action::make('Detach Organization')
                ->requiresConfirmation()
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
                })
                ->successRedirectUrl(fn (Ticket $record): string => route('filament.admin.resources.tickets.edit', [
                    'record' => $record,
                ])),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        if ($this->data['ticketable_id'] && $this->data['ticketable_type']) {
            return $this->previousUrl;
        } else {
            return $this->getResource()::getUrl('index');
        }
    }
}
