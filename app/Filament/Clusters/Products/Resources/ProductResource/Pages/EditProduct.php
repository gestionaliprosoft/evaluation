<?php

namespace App\Filament\Clusters\Products\Resources\ProductResource\Pages;

use App\Filament\Clusters\Products\Resources\ProductResource;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use App\Traits\HandleAttachments;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    use BaseSettings;
    use CommonSettings;
    use HandleAttachments;

    protected static string $resource = ProductResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }
}
