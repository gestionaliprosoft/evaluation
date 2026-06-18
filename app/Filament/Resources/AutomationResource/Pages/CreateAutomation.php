<?php

namespace App\Filament\Resources\AutomationResource\Pages;

use App\Filament\Resources\AutomationResource;
use App\Traits\BaseCreateSettings;
use App\Traits\CommonSettings;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAutomation extends CreateRecord
{
    use BaseCreateSettings;
    use CommonSettings;

    protected static string $resource = AutomationResource::class;

    protected static bool $canCreateAnother = false;

    protected function handleRecordCreation(array $data): Model
    {
        $automation = static::getModel()::create($data);

        $record = $automation;

        foreach ($this->data['automationConditions'] as $condition) {
            $record->automationConditions = $condition;
        }

        foreach ($this->data['automationActions'] as $action) {
            $record->automationActions = $action;
        }

        return $automation;
    }
}
