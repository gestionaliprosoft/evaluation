<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class MasterData extends Cluster
{
    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('navigations.clusters.master-data');
    }
}
