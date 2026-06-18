<?php

namespace App\Filament\Imports;

use App\Models\Picklist;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PicklistImporter extends Importer
{
    protected static ?string $model = Picklist::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('team_id')
                ->fillRecordUsing(function (Picklist $record, ?string $state): void {
                    $record->team_id = auth()->user()->team_id;
                })
                ->example(['', 2])
                ->requiredMapping()
                ->helperText('the team will be the one belonging to the User who is carrying out the import'),
            ImportColumn::make('module')
                ->example(['organization', 'lead'])
                ->requiredMapping(),
            ImportColumn::make('name')
                ->example(['industry', 'rating'])
                ->requiredMapping(),
            ImportColumn::make('items')
                ->castStateUsing(function (string $state) {
                    return json_decode($state);
                })
                ->example(
                    [
                        '[{"value": "Apparel", "enabled": true}, {"value": "Biotechnology", "enabled": true}]',
                        '[{"value": "Acquired", "enabled": true}, {"value": "Active", "enabled": true}]',
                    ]
                )
                ->requiredMapping(),
        ];
    }

    public function resolveRecord(): ?Picklist
    {
        return new Picklist;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your picklist import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
