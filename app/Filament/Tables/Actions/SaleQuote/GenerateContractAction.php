<?php

namespace App\Filament\Tables\Actions\SaleQuote;

use App\Libs\GenerateService;
use App\Models\Sale\SaleQuote;
use Filament\Tables\Actions\Action;

class GenerateContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'generateContractAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(fn (SaleQuote $record): bool => $record->deleted_at == null && ! $record->contract && auth()->user()->can('create', $record))
            ->label(__('Generate Contract'))
            ->requiresConfirmation()
            ->action(function (SaleQuote $record) {
                GenerateService::generateContract($record);
            });
    }
}
