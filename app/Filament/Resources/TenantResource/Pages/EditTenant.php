<?php

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Services\TenantService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTenant extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = TenantResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->label(__('Delete'))
                ->before(function ($record) {
                    // set no role for all users in that tenant
                    $users = $record->users;

                    foreach ($users as $user) {
                        $user->syncRoles([]);
                    }
                })
                ->after(fn () => redirect(self::getUrl(['index']))),
        ];
    }

    protected function afterSave()
    {
        if ($this->data['update_database']) {
            $tenantService = app(TenantService::class);

            $tenantService->updateDatabase($this->record);
        }
    }
}
