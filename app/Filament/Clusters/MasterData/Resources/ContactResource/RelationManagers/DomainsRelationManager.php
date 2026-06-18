<?php

namespace App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Clusters\Domains\Resources\DomainDomainResource;
use App\Models\Domain\DomainDomain;
use App\Traits\RelationManagers\DomainRelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DomainsRelationManager extends AbstractRelationManager
{
    use DomainRelationManager;

    protected static string $relationship = 'domains';

    public function table(Table $table): Table
    {
        return $table
            ->columns(DomainDomainResource::getColumnsComponents())
            ->filters(DomainDomainResource::getFiltersComponents())
            ->actions(array_merge(DomainDomainResource::getActionsComponents(), [
                static::completeFormAction(DomainDomainResource::class),
            ]))
            ->bulkActions(DomainDomainResource::getBulkActionsComponents())
            ->paginated(config('app.paginations.range'))
            ->defaultPaginationPageOption(config('app.paginations.table'))
            ->defaultSort('updated_at', 'desc')
            ->searchOnBlur(true)
            ->recordUrl(null);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return auth()->user()->can('viewAny', DomainDomain::class);
    }
}
