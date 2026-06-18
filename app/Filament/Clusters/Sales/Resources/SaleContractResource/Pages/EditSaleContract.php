<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Libs\GenerateService;
use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleContract extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments {
        afterSave as protected afterSaveTrait;
    }

    protected static string $resource = SaleContractResource::class;

    protected function getJollyField()
    {
        return 'Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Generate Project')
                ->visible(fn ($record): bool => $record->deleted_at == null && auth()->user()->can('create', ProjectProject::class))
                ->label(__('Generate Project'))
                ->requiresConfirmation()
                ->action(function (SaleContract $record) {
                    GenerateService::generateProject(
                        $record,
                        $record->date,
                        __('From').' '.__('ContractResource').' Nr '.$record->number.' '.__('Date ').$record->date.', '.$record->defaultModel?->name,
                        $record->description,
                        $record->total
                    );
                }),
            GenerateService::generateCommercialPdf('SaleContract', 'sale', true),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = self::setTeamIdFromUserId($data);

        if (! $data['details']) {
            $data['total'] = 0;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->afterSaveTrait();
    }
}
