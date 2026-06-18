<?php

namespace App\Traits\RelationManagers;

use App\Enums\TicketPriorityEnum;
use App\Filament\Clusters\Tickets\Resources\TicketResource;
use App\Libs\FormService;
use App\Libs\TicketService;
use App\Libs\WorkflowService;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketStatus;
use App\Models\User;
use App\Traits\Commentables\HasCommentableActions;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait TicketsRelationManager
{
    use HasCommentableActions;

    protected Form $form;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->tickets->count();
    }

    public function form(Form $form): Form
    {
        $formComponents = $form->schema(TicketResource::getFormsComponents());
        $this->form = $formComponents;

        return $formComponents;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(TicketResource::getColumnsComponents())
            ->filters(TicketResource::getFiltersComponents())
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('ticket.Add Ticket'))
                    ->visible(fn () => auth()->user()->can('create', Ticket::class))
                    ->modalHeading(__('ticket.Add Ticket'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'ticket_date' => now(),
                        'priority' => TicketPriorityEnum::NORMAL,
                        'ticketable_type' => $this->ticketableType == User::class ? null : $this->ticketableType,
                        'ticketable_id' => $this->ticketableType == User::class ? null : $livewire->ownerRecord->getKey(),
                        'ticket_category_id' => TicketService::getDefaultCategory(),
                        'team_id' => $livewire->ownerRecord->team_id,
                        'user_id' => $this->ticketableType == User::class ? $livewire->ownerRecord->id : $livewire->ownerRecord->user_id,
                        'ticket_status_id' => WorkflowService::getWorkFlowDefaultPermittedOption(TicketStatus::class, $livewire->ownerRecord),
                        'fromRelationManager' => true,
                    ])
                    ->modalWidth(MaxWidth::Full)
                    ->createAnother(false)
                    ->mutateFormDataUsing(function ($data) {
                        $data['uuid'] = Str::uuid();

                        return $data;
                    })
                    ->after(function ($data, RelationManager $livewire, Model $record) {
                        FormService::addAttachmentsToRelationManager(
                            $this->form->getRawState(),
                            $livewire->ownerRecord,
                            $record,
                            Ticket::class,
                            $data['title'],
                        );
                    }),
            ])
            ->actions([
                TicketResource::viewAction(),
                EditAction::make()
                    ->label('')
                    ->tooltip(__('Edit'))
                    ->modalWidth(MaxWidth::Full)
                    ->fillForm(function (Ticket $record): array {
                        $record->fromRelationManager = true;

                        return $record->toArray();
                    }),
                static::commentableAction(),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\DeleteAction::make()
                        ->label(__('Delete')),
                ]),
                static::completeFormAction(TicketResource::class),
            ])
            ->bulkActions(TicketResource::getBulkActionsComponents())
            ->defaultSort('ticket_date', 'desc');
    }
}
