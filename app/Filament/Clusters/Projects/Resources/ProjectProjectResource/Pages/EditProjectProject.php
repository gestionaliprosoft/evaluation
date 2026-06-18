<?php

namespace App\Filament\Clusters\Projects\Resources\ProjectProjectResource\Pages;

use App\Filament\Clusters\Projects\Resources\ProjectProjectResource;
use App\Models\Opportunity;
use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use App\Traits\BaseSettings;
use App\Traits\Commentables\HasCommentableActions;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProjectProject extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;
    use HasCommentableActions;

    protected static string $resource = ProjectProjectResource::class;

    protected function getJollyField()
    {
        return ' Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            static::commentableHeaderAction(),
            Actions\Action::make('Attach Contract')
                ->requiresConfirmation()
                ->label(__('project-project.Attach To Contract'))
                ->form([
                    Forms\Components\Select::make('contract_id')
                        ->label(__('ContractResource'))
                        ->options(fn (Get $get) => SaleContract::getOptionsForSelect($get('contract_id')))
                        ->searchable()
                        ->required(),
                ])
                ->visible(fn (ProjectProject $record) => ! $record->contract && auth()->user()->can('update', $record))
                ->action(function (array $data, ProjectProject $record) {
                    $record->contract_id = $data['contract_id'];
                    $record->project_value = SaleContract::where('id', $data['contract_id'])->value('total');
                    $record->update();

                    Notification::make()
                        ->title(__('project-project.Project Has Been Associated'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('Detach Contract')
                ->requiresConfirmation()
                ->label(__('project-project.Detach From Contract'))
                ->visible(fn (ProjectProject $record) => $record->contract && auth()->user()->can('update', $record))
                ->action(function (ProjectProject $record) {
                    $record->contract_id = null;
                    $record->update();

                    Notification::make()
                        ->title(__('project-project.Project Has Been Detached'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('Attach Opportunity')
                ->requiresConfirmation()
                ->label(__('project-project.Attach To Opportunity'))
                ->form([
                    Forms\Components\Select::make('opportunity_id')
                        ->label(__('resources.OpportunityResource'))
                        ->options(Opportunity::getOptionsForSelect())
                        ->searchable()
                        ->required(),
                ])
                ->visible(fn (ProjectProject $record) => ! $record->opportunity && auth()->user()->can('update', $record))
                ->action(function (array $data, ProjectProject $record) {
                    $record->opportunity_id = $data['opportunity_id'];
                    $record->project_value = Opportunity::where('id', $data['opportunity_id'])->value('opportunity_value');
                    $record->update();

                    Notification::make()
                        ->title(__('project-project.Project Has Been Associated'))
                        ->success()
                        ->send();
                }),
            Actions\Action::make('Detach Opportunity')
                ->requiresConfirmation()
                ->label(__('project-project.Detach From Opportunity'))
                ->visible(fn (ProjectProject $record) => $record->opportunity && auth()->user()->can('update', $record))
                ->action(function (ProjectProject $record) {
                    $record->opportunity_id = null;
                    $record->update();

                    Notification::make()
                        ->title(__('project-project.Project Has Been Detached'))
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
}
