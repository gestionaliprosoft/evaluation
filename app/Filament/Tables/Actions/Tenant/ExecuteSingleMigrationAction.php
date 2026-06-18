<?php

namespace App\Filament\Tables\Actions\Tenant;

use App\Models\Tenant;
use App\Services\DirectoryService;
use App\Services\TenantService;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;

class ExecuteSingleMigrationAction extends Action
{
    protected const DATABASE_MIGRATION_TENANT = 'database/migrations/tenants/';

    protected const DATABASE_MIGRATION = 'database/migrations/';

    public static function getDefaultName(): ?string
    {
        return 'executeSingleMigrationAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(fn (Tenant $record): bool => auth()->user()->isMainTenantSuperUser())
            ->label('Single Migration on this Tenant DB')
            ->icon('heroicon-o-circle-stack')
            ->color('danger')
            ->form([
                Select::make('migrationName')
                    ->label(__('Select Migration file to execute on this Tenant'))
                    ->options(function (DirectoryService $directoryService, Tenant $record) {
                        $path = $record->name !== config('app.main_tenant') ? self::DATABASE_MIGRATION_TENANT : self::DATABASE_MIGRATION;

                        return $directoryService->getAllFilesList(base_path($path));
                    })
                    ->optionsLimit(150)
                    ->searchable()
                    ->required(),
            ])
            ->requiresConfirmation()
            ->modalWidth(MaxWidth::ExtraLarge)
            ->modalDescription('Warning: this operation migrate selected Migration in selected Tenant!')
            ->action(function (array $data, TenantService $tenantService, Tenant $record) {
                // get Tenant record
                $path = $record->name !== config('app.main_tenant') ? self::DATABASE_MIGRATION_TENANT : self::DATABASE_MIGRATION;
                $tenantService->executeSingleMigration($path.$data['migrationName'], $record);
            });
    }
}
