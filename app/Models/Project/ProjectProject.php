<?php

namespace App\Models\Project;

use App\Filament\Clusters\Projects\Resources\ProjectProjectResource;
use App\Libs\PicklistService;
use App\Libs\WorkflowService;
use App\Models\Abstract\BaseModel;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Sale\SaleContract;
use App\Models\Team;
use App\Models\User;
use App\Observers\Projects\ProjectProjectObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Services\ModuleSettingService;
use App\Traits\Automationable;
use App\Traits\HasContacts;
use App\Traits\HasMembers;
use App\Traits\HasPaymentReceipts;
use App\Traits\HasTickets;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Kirschbaum\Commentions\Contracts\Commentable;
use Kirschbaum\Commentions\HasComments;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([ProjectProjectObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class ProjectProject extends BaseModel implements Commentable
{
    use Automationable;
    use HasComments;
    use HasContacts;
    use HasMembers;
    use HasPaymentReceipts;
    use HasTickets;
    use InteractsWithAttachments;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'projects';

    protected static $howManyFake = 6;

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'type',
        'progress',
    ];

    protected string $displayField = 'name';

    protected $fillable = [
        'number_seq',
        'team_id',
        'user_id',
        'uuid',
        'number',
        'date',
        'contract_id',
        'opportunity_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'real_end_date',
        'project_value',
        'type',
        'project_status_id',
        'progress',
        'contact_id',
        'organization_id',
    ];

    /**
     * Get Resource class name
     */
    public function getResourceClass(): string
    {
        return ProjectProjectResource::class;
    }

    public function getDisplayField(): string
    {
        return $this->displayField;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * A Project belong to Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * A Project belongsTo Contract
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(SaleContract::class, 'contract_id');
    }

    /**
     * A Project belongsTo Opportunity
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ProjectStatus::class, 'project_status_id');
    }

    public function getPicklists()
    {
        return $this->picklists;
    }

    /**
     * Check if the purchase order is currently is_default.
     */
    public function isDefault(): bool
    {
        return (bool) $this->status?->is_default;
    }

    /**
     * Check if the purchase order is currently is_editable.
     */
    public function isEditable(): bool
    {
        return (bool) $this->status?->is_editable;
    }

    /**
     * Check if the purchase order is currently is_final_step.
     */
    public function isFinalStep(): bool
    {
        return (bool) $this->status?->is_final_step;
    }

    /**
     * Check if the purchase order is currently archived.
     */
    public function isArchived(): bool
    {
        return (bool) $this->status?->archived;
    }

    /**
     * Summary of seedRecords (dont't add team_id, user_id fields, will be added by seeder)
     *
     * @return Collection>
     */
    public function seedRecords(): Collection
    {
        $moduleSettingService = app(ModuleSettingService::class);

        $data = collect();

        for ($i = 0; $i < self::$howManyFake; $i++) {
            $data->add([
                'number_seq' => $i + 1,
                'uuid' => fake()->uuid(),
                'number' => $moduleSettingService->getModuleSettings('ProjectProjects', 'number').($i + 1),
                'date' => now(),
                'contract_id' => null,
                'opportunity_id' => null,
                'name' => fake()->realTextBetween(15, 20),
                'description' => fake()->realText(30),
                'start_date' => fake()->dateTimeThisYear(),
                'end_date' => null,
                'real_end_date' => null,
                'project_value' => fake()->numberBetween(200, 1000),
                'type' => PicklistService::getPicklistsByFieldName('type', 'projectProject')->first(),
                'project_status_id' => WorkflowService::getWorkFlowDefaultPermittedOption(ProjectStatus::class),
                'progress' => 0,
                'contact_id' => null,
                'organization_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $data;
    }

    protected static function getHowmanyFake()
    {
        return self::$howManyFake;
    }

    /**
     * Get the complete project list formatted for Filament Select components,
     * taking into account the user's role, multi-team tenancy, ownership, memberships,
     * and forcing the inclusion of the currently selected record.
     */
    public static function getOptionsForSelect(int|string|null $currentId = null): array|Collection
    {
        $user = auth()->user();

        // Prevent errors if no user is currently authenticated
        if (! $user) {
            return [];
        }

        // Standard user flow: filter by team, ownership, or explicit membership
        if (! $user->hasRole(['super_admin'])) {
            return static::where('team_id', $user->team_id)
                ->where(function ($query) use ($user, $currentId) {
                    // Standard restriction path: must be owned or membered
                    $query->where(function ($subQuery) use ($user) {
                        $subQuery->where('user_id', $user->id)
                            ->orWhereHas('members', function ($memberQuery) use ($user) {
                                $memberQuery->where('user_id', $user->id);
                            });
                    });

                    // Historical fallback path: force-include the current record within tenant bounds
                    if ($currentId) {
                        $query->orWhere('id', $currentId);
                    }
                })
                ->pluck('name', 'id');
        }

        // Super admin flow: fetch projects grouped by team name
        return static::select(['id', 'team_id', 'name'])
            ->with('team')
            ->where(function ($query) use ($currentId) {
                // Force-include the current historical record for super admin as well
                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->get()
            ->groupBy(function ($project) {
                return $project->team?->name ? 'Team '.$project->team->name : 'No Team';
            })
            ->map(function ($group) {
                return $group->pluck('name', 'id');
            });
    }
}
