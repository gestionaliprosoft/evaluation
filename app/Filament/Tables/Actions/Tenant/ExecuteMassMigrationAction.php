<?php

namespace App\Filament\Tables\Actions\Tenant;

use App\Models\Tenant;
use App\Services\TenantService;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;

class ExecuteMassMigrationAction extends Action
{
    protected const DATABASE_MIGRATION_TENANT = 'database/migrations/tenants/';

    protected const DATABASE_MIGRATION = 'database/migrations/';

    public static function getDefaultName(): ?string
    {
        return 'executeMassMigrationAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(fn (): bool => auth()->user()->isMainTenantSuperUser())
            ->label('Migrations on this Tenant DB')
            ->icon('heroicon-o-circle-stack')
            ->color('danger')
            ->requiresConfirmation()
            ->modalWidth(MaxWidth::Large)
            ->modalDescription('Warning: this operation execute Migration in selected Tenant!')
            ->action(function (TenantService $tenantService, Tenant $record) {
                // get Tenant record
                $path = $record->name !== config('app.main_tenant') ? self::DATABASE_MIGRATION_TENANT : self::DATABASE_MIGRATION;
                $tenantService->executeSingleMigration($path, $record);
            });
    }
}
