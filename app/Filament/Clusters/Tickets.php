<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Tickets extends Cluster
{
    protected static ?int $navigationSort = 8;

    public static function getNavigationLabel(): string
    {
        return __('navigations.clusters.tickets');
    }
}
