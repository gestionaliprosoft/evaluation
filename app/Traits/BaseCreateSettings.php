<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Arr;

trait BaseCreateSettings
{
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (Arr::exists($data, 'user_id')) {
            $teamId = User::whereId($data['user_id'])->first()->team?->id;
            $data['team_id'] = $teamId ?? null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ? $this->previousUrl : $this->getResource()::getUrl('index');
    }
}
