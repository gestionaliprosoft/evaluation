<?php

namespace App\Traits;

use App\Models\User;
use App\Services\TeamService;
use App\Traits\Actions\HasTableActions;
use App\Traits\Columns\HasColumns;
use App\Traits\Filters\HasFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait BaseSettings
{
    use HasColumns;
    use HasFilters;
    use HasTableActions;

    /**
     * Retrieves the associated Eloquent model instance using Lazy Loading.
     */
    protected static function getModelInstance(): Model
    {
        // Resolve a clean instance locally
        $modelClass = static::getModel();

        return new $modelClass;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->setTeamIdFromUserId($data);
    }

    public static function defineTable($table)
    {
        $modelInstance = static::getModelInstance();

        if (static::showTimestamps() && (new $modelInstance)->usesTimestamps()) {
            $columns = array_merge(
                static::getColumnsComponents(),
                [
                    static::dateTimeColumn('created_at', __('Created At'))->toggleable(isToggledHiddenByDefault: true),
                    static::dateTimeColumn('updated_at', __('Updated At'))->toggleable(isToggledHiddenByDefault: true),
                ]
            );
        } else {
            $columns = static::getColumnsComponents();
        }

        $usesSoftDeletes = in_array(
            SoftDeletes::class,
            class_uses_recursive($modelInstance)
        );

        if ($usesSoftDeletes) {
            $filters = array_merge(
                static::getFiltersComponents(),
                [
                    static::trashedFilter(),
                ]
            );
        } else {
            $filters = static::getFiltersComponents();
        }

        return $table
            ->deferLoading()
            ->columns($columns)
            ->filters($filters)->deferFilters()->persistFiltersInSession()
            ->filtersFormColumns(2)
            ->actions(static::getActionsComponents())
            ->bulkActions(static::getBulkActionsComponents())->selectCurrentPageOnly()
            ->searchOnBlur(false)
            ->paginated(config('app.paginations.range'))
            ->defaultPaginationPageOption(config('app.paginations.table'));
    }

    public static function defineForm($form)
    {
        return $form->schema(static::getFormsComponents());
    }

    public static function getNavigationBadge(): ?string
    {
        $modelInstance = static::getModelInstance();

        return $modelInstance->count();

        // Resolve the service from the container
        $teamService = app(TeamService::class);
        $teamIds = $teamService->getTenantTeamsIds();

        // --- Check for user_id column or if User is Super Admin and apply filter
        // if no user_id is generic for all Teams so count all belong to Users Team Super Admin or Simple User
        // if no user_id column impossible to apply members relationship
        if (! in_array('user_id', $modelInstance->getFillable()) || auth()->user()->hasRole(['super_admin'])) {
            return $modelInstance::whereIn('team_id', $teamIds)->orWhereNull('team_id')->count();
        } else {
            // has user_id field so try members relationship
            return $modelInstance->isRelation('members')
                ? $modelInstance::where('user_id', auth()->user()->getKey())
                    ->orWhereHas('members', function (Builder $query) {
                        $query->where('modules_members.user_id', auth()->user()->getKey());
                    })->count()
                : $modelInstance::where('user_id', auth()->user()->getKey())->count();

        }
    }

    public static function getNavigationIcon(): string
    {
        return config('module-icon.'.Str::afterLast(static::class, '\\')) ?? config('module-icon.default');
    }

    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    public function getContentTabLabel(): ?string
    {
        return __('resources.'.Str::afterLast($this->getResource(), '\\'));
    }

    public function setTeamIdFromUserId($data)
    {
        if (Arr::exists($data, 'user_id')) {
            $teamId = User::whereId($data['user_id'])->first()->team?->id;

            $data['team_id'] = $teamId ?? null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ? $this->previousUrl : $this->getResource()::getUrl('index');
    }

    protected static function showTimestamps()
    {
        return true;
    }
}
