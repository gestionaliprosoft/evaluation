<?php

namespace App\Filament\Exports;

use App\Models\Domain\DomainDomain;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class DomainDomainExporter extends Exporter
{
    protected static ?string $model = DomainDomain::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label(__('#Id')),
            ExportColumn::make('name')->label(__('Name')),
            ExportColumn::make('contactOrganization')
                ->state(function ($record) {
                    $contacts = collect();

                    if ($record->contact) {
                        $contacts->push($record->contact->full_name);
                    }

                    if ($record->contact?->organization) {
                        $contacts->push($record->contact->organization->name);
                    }

                    return $contacts->implode(', ');
                })
                ->label(__('resources.ContactResource')),
            ExportColumn::make('username')->label(__('Username/Login')),
            
            ExportColumn::make('expire_date')->label(__('Expire date')),
            ExportColumn::make('enabled')
                ->label(__('Enabled'))
                ->formatStateUsing(fn ($state) => $state ? __('Yes') : __('No')),
            ExportColumn::make('team.name')->label(__('resources.TeamResource')),
            ExportColumn::make('user.fullName')->label(__('resources.UserResource')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your domain domain export has completed and '.number_format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
