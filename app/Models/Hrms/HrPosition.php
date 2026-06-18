<?php

namespace App\Models\Hrms;

use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Models\User;
use App\Observers\Hrms\HrPositionObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HrPositionObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class HrPosition extends BaseModel
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hr_positions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function hrEmployees(): HasMany
    {
        return $this->hasMany(HrEmployee::class, 'id', 'hr_position_id');
    }

    /**
     * Get the complete positions list formatted for Filament Select components,
     * taking into account the user's role, multi-team tenancy, ownership, dynamic memberships,
     * and forcing the inclusion of the currently selected record.
     */
    public static function getOptionsForSelect(int|string|null $currentId = null): array|Collection
    {
        $user = auth()->user();

        // Prevent errors if no user is currently authenticated
        if (! $user) {
            return [];
        }

        // Base query setup with eager loading needed for the labels
        $baseQuery = static::select(['id', 'team_id', 'name', 'description', 'user_id'])
            ->with(['team']);

        // Standard user flow: filter by team, ownership, or explicit membership (if available)
        if (! $user->hasRole(['super_admin'])) {
            return $baseQuery->where('team_id', $user->team_id)
                ->where(function ($query) use ($user, $currentId) {
                    // Standard restriction path: must be owned or membered
                    $query->where(function ($subQuery) use ($user) {
                        // In base alla tua logica originale, l'utente standard vede se stesso legato al record
                        $subQuery->where('id', $user->id);

                        // Dynamic check: apply membership filter only if relation exists on the model
                        if (method_exists(static::class, 'members')) {
                            $subQuery->orWhereHas('members', function ($memberQuery) use ($user) {
                                $memberQuery->where('user_id', $user->id);
                            });
                        }
                    });

                    // Historical fallback path: force-include the current record within tenant bounds
                    if ($currentId) {
                        $query->orWhere('id', $currentId);
                    }
                })
                ->get()
                ->mapWithKeys(function ($position) {
                    $label = $position->name.($position->description ? ' ('.$position->description.')' : '');

                    return [$position->id => $label];
                });
        }

        // Super admin flow: fetch positions grouped by team name
        return $baseQuery->where(function ($query) use ($currentId) {
            // Force-include the current historical record for super admin as well
            if ($currentId) {
                $query->orWhere('id', $currentId);
            }
        })
            ->get()
            ->groupBy(function ($position) {
                return $position->team?->name ? 'Team '.$position->team->name : 'No Team';
            })
            ->map(function ($group) {
                return $group->mapWithKeys(function ($position) {
                    $label = $position->name.($position->description ? ' ('.$position->description.')' : '');

                    return [$position->id => $label];
                });
            });
    }
}
