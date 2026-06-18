<?php

namespace App\Filament\Clusters\Sales\Resources\SaleQuoteResource\Pages;

use App\Filament\Clusters\Sales\Resources\SaleQuoteResource;
use App\Libs\GenerateService;
use App\Models\Sale\SaleContract;
use App\Models\Sale\SaleQuote;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSaleQuote extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = SaleQuoteResource::class;

    protected function getJollyField()
    {
        return 'Nr. '.$this->record->number;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Generate Contract')
                ->visible(fn (SaleQuote $record): bool => $record->deleted_at == null && ! $record->contract && auth()->user()->can('create', SaleContract::class))
                ->label(__('Generate Contract'))
                ->requiresConfirmation()
                ->action(function (SaleQuote $record) {
                    GenerateService::generateContract($record);
                }),
            GenerateService::generateCommercialPdf('SaleQuote', 'sale', true),
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
}
