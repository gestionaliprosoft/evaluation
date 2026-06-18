<?php

namespace App\Models\Hrms;

use App\Filament\Clusters\Hrms\Resources\HrEmployeeResource;
use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Models\User;
use App\Observers\Hrms\HrEmployeeObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HrEmployeeObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class HrEmployee extends BaseModel
{
    use InteractsWithAttachments;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hr_employees';

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'gender',
        'marital_status',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'linked_user_id',
        'hr_department_id',
        'hr_position_id',
        'hr_salary_id',
        'gender',
        'id_employee',

        'primary_phone',
        'mobile_phone',
        'secondary_email',
        'birth_date',
        'birth_place',

        'address',
        'city',
        'state',
        'zip',
        'country',

        'marital_status',

        'hiring_date',
    ];

    /**
     * Get Resource class
     */
    public function getResourceClass(): string
    {
        return HrEmployeeResource::class;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function linkedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_user_id', 'id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(HrDepartment::class, 'hr_department_id', 'id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(HrPosition::class, 'hr_position_id', 'id');
    }

    public function salary(): BelongsTo
    {
        return $this->belongsTo(HrSalary::class, 'hr_salary_id', 'id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(HrLeaveRequest::class, 'id', 'hr_employee_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(HrAttendance::class, 'id', 'hr_employee_id');
    }

    public function getPicklists()
    {
        return $this->picklists;
    }

    /**
     * Get the complete employees list formatted for Filament Select components,
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
        $baseQuery = static::select(['id', 'team_id', 'linked_user_id', 'user_id'])
            ->with(['team', 'linkedUser']);

        // Standard user flow: filter by team, ownership, or explicit membership (if available)
        if (! $user->hasRole(['super_admin'])) {
            return $baseQuery->where('team_id', $user->team_id)
                ->where(function ($query) use ($user, $currentId) {
                    // Standard restriction path: must be owned or membered
                    $query->where(function ($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id);

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
                ->mapWithKeys(function ($employee) {
                    $label = ($employee->linkedUser?->fullName ?? 'No Name').' ('.($employee->linkedUser?->email ?? 'No Email').')';

                    return [$employee->id => $label];
                });
        }

        // Super admin flow: fetch employees grouped by team name
        return $baseQuery->where(function ($query) use ($currentId) {
            // Force-include the current historical record for super admin as well
            if ($currentId) {
                $query->orWhere('id', $currentId);
            }
        })
            ->get()
            ->groupBy(function ($employee) {
                return $employee->team?->name ? 'Team '.$employee->team->name : 'No Team';
            })
            ->map(function ($group) {
                return $group->mapWithKeys(function ($employee) {
                    $label = ($employee->linkedUser?->fullName ?? 'No Name').' ('.($employee->linkedUser?->email ?? 'No Email').')';

                    return [$employee->id => $label];
                });
            });
    }
}
