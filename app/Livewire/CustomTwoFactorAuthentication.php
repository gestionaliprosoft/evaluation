<?php

namespace App\Livewire;

use Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication;

class CustomTwoFactorAuthentication extends TwoFactorAuthentication
{
    public static function canView(): bool
    {
        return auth()->user()->tenant->id !== (int) config('demo.demo_default_tenant_id');
    }
}
