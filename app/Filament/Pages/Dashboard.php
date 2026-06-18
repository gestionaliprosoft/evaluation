<?php

namespace App\Filament\Pages;

class Dashboard extends \Filament\Pages\Dashboard
{
    public function getTitle(): string
    {
        return __('dashboard.Dashboard');
    }

    public function getColumns(): int|string|array
    {
        return 3;
    }
}
