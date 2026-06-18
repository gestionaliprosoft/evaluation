<?php

namespace App\Filament\Tables\Actions\SaleContract;

use App\Libs\GenerateService;
use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use Filament\Tables\Actions\Action;

class GenerateProjectAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateProjectAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(fn ($record): bool => $record->deleted_at == null &&
            $record->status?->is_final_step &&
            auth()->user()->can('create', ProjectProject::class)
        )
            ->label(__('Generate Project'))
            ->requiresConfirmation()
            ->action(function (SaleContract $record) {
                GenerateService::generateProject(
                    $record,
                    $record->date,
                    __('From').' '.__('ContractResource').' Nr: '.$record->number.' '.__('Date: ').$record->date.', '.$record->defaultModel->name,
                    $record->description,
                    $record->total
                );
            });
    }
}
