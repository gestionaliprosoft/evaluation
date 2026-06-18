<?php

namespace App\Traits;

use App\Traits\Actions\HasHeaderActions;

trait BaseViewSettings
{
    use HasHeaderActions;

    protected function getHeaderActions(): array
    {
        return [
            static::editAction(),
            static::createAction(),
        ];
    }
}
