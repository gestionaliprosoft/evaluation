<?php

namespace App\Abstracts\RelationManagers;

use App\Traits\Actions\HasTableActions;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

abstract class AbstractRelationManager extends RelationManager
{
    use HasTableActions;

    protected string $jollyField;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relations-managers.'.static::$relationship);
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): ?string
    {
        $model = $ownerRecord->{static::getRelationshipName()}()->getQuery()->getModel()::class;
        $resourceName = Str::afterLast($model, '\\').'Resource';

        return config('module-icon.'.Str::afterLast($resourceName, '\\')) ?? config('module-icon.default');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        $toCheck = $ownerRecord->{static::$relationship};

        if ($toCheck instanceof EloquentCollection || $toCheck instanceof SupportCollection) {
            return self::checkEloquentOrSupportCollection($toCheck);
        } elseif ($toCheck instanceof Model) {
            return self::checkEloquentModel($toCheck);
        } else {
            return false;
        }
    }

    protected static function checkEloquentOrSupportCollection($toCheck): bool
    {
        if ($toCheck->isNotEmpty()) {
            return true;
        }

        return false;
    }

    protected static function checkEloquentModel($toCheck): bool
    {
        if ($toCheck) {
            return true;
        }

        return false;
    }
}
