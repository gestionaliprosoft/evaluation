<?php

namespace App\Filament\Tables\Actions\ProjectProject;

use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class AttachContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'attachContractAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
            ->label(__('project-project.Attach To Contract'))
            ->form([
                Select::make('contract_id')
                    ->label(__('resources.SaleContractResources'))
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
            });
    }
}
