<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Purchases extends Cluster
{
    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('navigations.clusters.purchases');
    }
}
