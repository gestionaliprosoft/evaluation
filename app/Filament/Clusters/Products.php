<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Products extends Cluster
{
    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('navigations.clusters.products');
    }
}
