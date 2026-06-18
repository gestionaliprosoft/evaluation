<?php

namespace App\Livewire;

use Jeffgreco13\FilamentBreezy\Livewire\UpdatePassword;

class CustomUpdatePassword extends UpdatePassword
{
    public static function canView(): bool
    {
        return auth()->user()->tenant->id !== (int) config('demo.demo_default_tenant_id');
    }
}
