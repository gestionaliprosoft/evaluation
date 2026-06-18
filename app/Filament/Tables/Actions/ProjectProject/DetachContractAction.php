<?php

namespace App\Filament\Tables\Actions\ProjectProject;

use App\Models\Project\ProjectProject;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class DetachContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'detachContractAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->requiresConfirmation()
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
            });
    }
}
