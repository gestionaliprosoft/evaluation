<?php

namespace App\Filament\Imports;

use App\Models\Project\ProjectStatus;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class ProjectStatusImporter extends Importer
{
    protected static ?string $model = ProjectStatus::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('team_id')
                ->fillRecordUsing(function (ProjectStatus $record, ?string $state): void {
                    $record->team_id = auth()->user()->team_id;
                })
                ->example(['', 2])
                ->requiredMapping()
                ->helperText('the team will be the one belonging to the User who is carrying out the import'),
            ImportColumn::make('status')
                ->example(['Open', 'Closed'])
                ->requiredMapping(),
            ImportColumn::make('is_default')
                ->example([1, 0])
                ->requiredMapping(),
            ImportColumn::make('is_editable')
                ->example([1, 0])
                ->requiredMapping(),
            ImportColumn::make('is_final_step')
                ->example([1, 0])
                ->requiredMapping(),
            ImportColumn::make('archived')
                ->example([1, 0])
                ->requiredMapping(),
            ImportColumn::make('sorting')
                ->example([0, 1])
                ->requiredMapping(),
        ];
    }

    public function resolveRecord(): ?ProjectStatus
    {
        return new ProjectStatus;
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your project statuses import has completed and '.number_format($import->successful_rows).' '.str('row')->plural($import->successful_rows).' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to import.';
        }

        return $body;
    }
}
