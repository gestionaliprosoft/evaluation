<?php

namespace App\Filament\Tables\Actions\SaleContract;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Models\Sale\SaleContract;
use App\Models\Sale\SaleContractModel;
use App\Services\ContractService;
use App\Services\ModuleSettingService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Str;

class RenewContractAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'renewContractAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(fn ($record): bool => Carbon::parse($record->valid_until) < now() &&
            auth()->user()->can('create', $record)
        )
            ->label(__('sale-contract.Renew Contract'))
            ->requiresConfirmation()
            ->action(function (SaleContract $record, ModuleSettingService $moduleSettingService) {
                $renewdContract = $record->replicate();
                $renewdContract->number_seq = SaleContract::where('team_id', $record['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
                $renewdContract->uuid = Str::uuid();
                $renewdContract->number = $moduleSettingService->getModuleSettings('SaleContracts', 'number').$renewdContract['number_seq'];
                $renewdContract->date = now();
                $renewdContract->valid_from = now()->addDay();

                $contractModel = SaleContractModel::where('id', $record->contract_model_id)->first();
                $renewdContract->valid_until = now()->addDays((int) $contractModel?->validity_days);

                $renewdContract->acceptance_date = now();
                $renewdContract->contract_status_id = ContractService::getDefaultStatus();

                try {
                    $renewdContract->save();

                    Notification::make()
                        ->title(__('sale-contract.Contract Has been Renewed'))
                        ->success()
                        ->send();

                    return redirect(SaleContractResource::getUrl('edit', ['record' => $renewdContract]));
                } catch (\Throwable $th) {
                    Notification::make()
                        ->title(__('Error Renewing Contract'))
                        ->body($th->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
