<?php

namespace App\Filament\Imports;

use App\Models\Accounting\PaymentMethod;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PaymentMethodImporter extends Importer
{
    protected static ?string $model = PaymentMethod::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('team_id')
                ->fillRecordUsing(function (PaymentMethod $record, ?string $state): void {
                    $record->team_id = auth()->user()->team_id;
                })
                ->example(['', 2])
                ->requiredMapping()
                ->helperText('the team will be the one belonging to the User who is carrying out the import'),
            ImportColumn::make('user_id')
                ->fillRecordUsing(function (PaymentMethod $record, ?string $state): void {
                    $record->user_id = auth()->user()->getKey();
                })
                ->example(['', 1])
                ->requiredMapping()
                ->helperText('the user will be the User who is carrying out the import'),
            ImportColumn::make('name')
                ->example(['Bank transfer name', 'Paypal'])
                ->requiredMapping(),
            ImportColumn::make('description')
                ->example(['Bank transfer Description', 'Paypal Description'])
                ->requiredMapping(),
            ImportColumn::make('details')
                ->example(['Bank Transfer Details', 'Paypal Details'])
                ->requiredMapping(),
        ];
    }

    public function resolveRecord(): ?PaymentMethod
    {
        return new PaymentMethod;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your payment methods import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
