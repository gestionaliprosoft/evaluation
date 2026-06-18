<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Projects extends Cluster
{
    protected static ?int $navigationSort = 6;

    public static function getNavigationLabel(): string
    {
        return __('navigations.clusters.projects');
    }
}
