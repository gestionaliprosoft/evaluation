<?php

namespace App\Models\Hrms;

use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Models\User;
use App\Observers\Hrms\HrDepartmentObserver;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HrDepartmentObserver::class])]
#[ScopedBy(TeamScope::class)]
class HrDepartment extends BaseModel
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hr_departments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'manager_id',
        'name',
        'description',
        'address',
        'city',
        'state',
        'zip',
        'country',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hrEmployees(): HasMany
    {
        return $this->hasMany(HrEmployee::class, 'id', 'hr_department_id');
    }

    /**
     * Get the complete departments list formatted for Filament Select components,
     * taking into account multi-team tenancy and forcing inclusion of the current record.
     */
    public static function getOptionsForSelect(int|string|null $currentId = null): array|Collection
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        // Base query (solo team_id, nessun owner)
        $baseQuery = static::select(['id', 'team_id', 'name', 'description'])
            ->with('team');

        // --- STANDARD USER FLOW (team-scoped) ---
        if (! $user->hasRole(['super_admin'])) {
            return $baseQuery
                ->where('team_id', $user->team_id)
                ->where(function ($query) use ($currentId) {
                    // Force include current record
                    if ($currentId) {
                        $query->orWhere('id', $currentId);
                    }
                })
                ->orderBy('name')
                ->get()
                ->mapWithKeys(function ($department) {
                    $label = $department->name
                        .($department->description ? ' ('.$department->description.')' : '');

                    return [$department->id => $label];
                });
        }

        // --- SUPER ADMIN FLOW (grouped by team) ---
        return $baseQuery
            ->where(function ($query) use ($currentId) {
                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->orderBy('name')
            ->get()
            ->groupBy(function ($department) {
                return $department->team?->name
                    ? 'Team '.$department->team->name
                    : 'No Team';
            })
            ->map(function ($group) {
                return $group->mapWithKeys(function ($department) {
                    $label = $department->name
                        .($department->description ? ' ('.$department->description.')' : '');

                    return [$department->id => $label];
                });
            });
    }
}
