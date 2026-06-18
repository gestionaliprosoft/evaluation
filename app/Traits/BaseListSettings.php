<?php

namespace App\Traits;

use App\Traits\Actions\HasHeaderActions;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;

trait BaseListSettings
{
    use HasHeaderActions;

    public function getHeading(): string|Htmlable
    {
        return __('resources.'.Str::afterLast($this->getResource(), '\\').'s');
    }

    protected function getHeaderActions(): array
    {
        return [
            static::createAction(),
        ];
    }
}
