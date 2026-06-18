<?php

namespace App\Models\Hrms;

use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Models\User;
use App\Observers\Hrms\HrSalaryObserver;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HrSalaryObserver::class])]
#[ScopedBy(TeamScope::class)]
class HrSalary extends BaseModel
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hr_salaries';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'items' => 'array',
        ];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'name',
        'hr_salary_type',
        'items',
        'total',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function salaryType(): BelongsTo
    {
        return $this->belongsTo(HrSalaryType::class, 'hr_salary_type', 'id');
    }

    public function employees(): HasMany
    {
        return $this->hasMany(HrEmployee::class, 'id', 'hr_salary_id');
    }

    /**
     * Get the complete salaries list formatted for Filament Select components,
     * taking into account multi-team tenancy, ownership,
     * and forcing the inclusion of the currently selected record.
     */
    public static function getOptionsForSelect(int|string|null $currentId = null): array|Collection
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        // Base query (team-scoped)
        $baseQuery = static::select(['id', 'team_id', 'hr_salary_type'])
            ->with(['team', 'salaryType']);

        // Helper per generare la label
        $formatLabel = function ($salary) {
            $name = $salary->salaryType?->name ?? 'No Type';
            $desc = $salary->salaryType?->description;

            return $desc ? "{$name} ({$desc})" : $name;
        };

        // STANDARD USER FLOW: scope by team only (user_id removed)
        if (! $user->hasRole(['super_admin'])) {
            $results = $baseQuery
                ->where('team_id', $user->team_id)
                ->when($currentId, fn ($q) => $q->orWhere('id', $currentId))
                ->orderBy('id')
                ->get();

            return $results->mapWithKeys(function ($salary) use ($formatLabel) {
                return [$salary->id => $formatLabel($salary)];
            });
        }

        // SUPER ADMIN FLOW: all teams, grouped by team name, always include currentId if present
        $results = $baseQuery
            ->when($currentId, fn ($q) => $q->orWhere('id', $currentId))
            ->orderBy('id')
            ->get()
            ->groupBy(fn ($salary) => $salary->team?->name ? 'Team '.$salary->team->name : 'No Team');

        return $results->map(function ($group) use ($formatLabel) {
            return $group->mapWithKeys(function ($salary) use ($formatLabel) {
                return [$salary->id => $formatLabel($salary)];
            });
        });
    }
}
