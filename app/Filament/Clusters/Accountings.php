<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Accountings extends Cluster
{
    protected static ?int $navigationSort = 7;

    public static function getNavigationLabel(): string
    {
        return __('navigations.clusters.accountings');
    }
}
