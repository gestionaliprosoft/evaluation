<?php

namespace App\Models\Ticket;

use App\Enums\TicketPriorityEnum;
use App\Filament\Clusters\Tickets\Resources\TicketResource;
use App\Models\Abstract\BaseModel;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Observers\Tickets\TicketObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\Automationable;
use App\Traits\HasMembers;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Kirschbaum\Commentions\Contracts\Commentable;
use Kirschbaum\Commentions\HasComments;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([TicketObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class Ticket extends BaseModel implements Commentable
{
    use Automationable;
    use HasComments;
    use HasMembers;
    use InteractsWithAttachments;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tickets';

    protected static $howManyFake = 6;

    protected $casts = [
        'priority' => TicketPriorityEnum::class,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'team_id',
        'user_id',
        'ticket_category_id',
        'ticket_intervention_id',
        'contact_id',
        'organization_id',
        'ticket_date',
        'title',
        'message',
        'priority',
        'ticket_status_id',
        'ticketable_id',
        'ticketable_type',
        'close_date',
    ];

    /**
     * Get Resource class
     */
    public function getResourceClass(): string
    {
        return TicketResource::class;
    }

    /**
     * Get the parent ticketable model
     */
    public function ticketable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function ticketCategory(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'ticket_category_id', 'id');
    }

    public function ticketIntervention(): BelongsTo
    {
        return $this->belongsTo(TicketIntervention::class, 'ticket_intervention_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'ticket_status_id');
    }

    /**
     * Get Comments RelationShip
     */
    /* public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id', 'id');
    } */

    /**
     * Get Times RelationShip
     */
    public function times(): HasMany
    {
        return $this->hasMany(TicketTime::class, 'ticket_id', 'id');
    }

    /**
     * A Ticket BelongsTo Contact
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * A Ticket belong to Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
        $data = collect();

        for ($i = 0; $i < self::$howManyFake; $i++) {
            $data->add([
                'uuid' => Str::uuid(),
                'ticket_category_id' => 4,
                'ticket_intervention_id' => 3,
                'contact_id' => null,
                'organization_id' => $i + 1,
                'ticket_date' => now(),
                'title' => fake()->text(20),
                'message' => fake()->text(50),
                'priority' => TicketPriorityEnum::NORMAL,
                'ticket_status_id' => 1,
                'ticketable_id' => $i + 1,
                'ticketable_type' => 'App\\Models\\Project\\ProjectProject',
                'close_date' => null,
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
}
