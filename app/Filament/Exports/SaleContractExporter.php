<?php

namespace App\Filament\Exports;

use App\Libs\UserService;
use App\Models\Contact;
use App\Models\ModuleContact;
use App\Models\Sale\SaleContract;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Collection;

class SaleContractExporter extends Exporter
{
    protected static ?string $model = SaleContract::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label(__('#Id')),
            ExportColumn::make('number')->label(__('Nr.')),
            ExportColumn::make('date')->label(__('Date')),
            ExportColumn::make('defaultModel.name')->label(__('sale-contract.Model')),
            ExportColumn::make('organization.name')->label(__('resources.OrganizationResource')),
            ExportColumn::make('contacts')
                ->label(__('resources.ContactResource'))
                ->state(function ($record) {
                    $contacts = $record->organization
                        ? $record->organization->contacts->map(function (ModuleContact $record) {
                            return Contact::where('id', $record->contact_id)->value('full_name');
                        })
                        : new Collection;

                    if ($contacts->isEmpty() && $record->contact) {
                        $contacts[] = $record->contact->full_name;
                    }

                    return $contacts->implode(', ');
                }),

            ExportColumn::make('contract_status_id')
                ->label(__('Status'))
                ->state(fn (SaleContract $record) => $record->status->status),
            ExportColumn::make('acceptance_date')->label(__('sale-contract.Acceptance Date')),
            ExportColumn::make('valid_from')->label(__('sale-contract.Valid From')),
            ExportColumn::make('valid_until')->label(__('sale-contract.Valid Until')),
            ExportColumn::make('total')
                ->label(__('sale-contract.Total'))
                ->prefix(UserService::getCurrencyPrefix()),
            ExportColumn::make('team.name')->label(__('resources.TeamResource')),
            ExportColumn::make('user.fullName')->label(__('resources.UserResource')),

        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your sale contract export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
