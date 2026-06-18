<?php

namespace App\Models;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Models\Abstract\BaseModel;
use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use App\Models\Sale\SaleQuote;
use App\Observers\OrganizationObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\Automationable;
use App\Traits\HasAddresses;
use App\Traits\HasContacts;
use App\Traits\HasDomains;
use App\Traits\HasMembers;
use App\Traits\HasTickets;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasEmailMessages;

#[ObservedBy([OrganizationObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class Organization extends BaseModel
{
    use Automationable;
    use HasAddresses;
    use HasContacts;
    use HasDomains;
    use HasMembers;
    use HasTickets;
    use HasEmailMessages;
    use InteractsWithAttachments;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'organizations';

    protected static $howManyFake = 6;

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'industry',
        'rating',
        'type',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'name',
        'primary_phone',
        'secondary_phone',
        'mobile_phone',
        'legal_representative',
        'primary_email',
        'secondary_email',

        'employees',
        'vat',
        'tax_id_code',

        'website',

        'industry',
        'rating',
        'type',

        'description',
    ];

    /**
     * Get Resource class
     */
    public function getResourceClass(): string
    {
        return OrganizationResource::class;
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
     * Get the parent contactable model
     */
    public function contacts(): MorphMany
    {
        return $this->morphMany(ModuleContact::class, 'contactable');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(SaleQuote::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(SaleContract::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ProjectProject::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(OrganizationDetail::class, 'organization_id');
    }

    public function getPicklists()
    {
        return $this->picklists;
    }

    // Overriding the default null behavior
    public function getRecipientEmail(): ?string
    {
        return $this->primary_email;
    }

    // Overriding the default null behavior
    public function getRecipientName(): ?string
    {
        return "{$this->name} ({$this->legal_representative})";
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
                'name' => fake()->company(),
                'primary_phone' => fake()->phoneNumber(),
                'secondary_phone' => fake()->phoneNumber(),
                'mobile_phone' => fake()->phoneNumber(),
                'legal_representative' => fake()->name(),
                'primary_email' => fake()->companyEmail(),
                'secondary_email' => fake()->companyEmail(),

                'employees' => fake()->randomNumber(2),
                'vat' => null,
                'tax_id_code' => null,

                'website' => fake()->url(),

                'industry' => null,
                'rating' => null,
                'type' => null,

                'description' => null,

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
     * Get the organization list formatted for Filament Select components,
     * taking into account the user's role, multi-team tenancy constraints,
     * and the morphMany ModuleContact relationship filter.
     */
    public static function getOptionsForSelect(int|string|null $contact = null): array|Collection
    {
        $user = auth()->user();

        // Prevent errors if no user is currently authenticated
        if (! $user) {
            return [];
        }

        // Standard user flow: filter by team, ownership, or explicit membership
        if (! $user->hasRole(['super_admin'])) {
            return static::where('team_id', $user->team_id)
                ->where(function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                        ->orWhereHas('members', function ($subQuery) use ($user) {
                            $subQuery->where('user_id', $user->id);
                        });
                })
                // Query through morphMany (ModuleContact) checking its local 'contact_id' column
                ->when($contact, function ($query) use ($contact) {
                    $query->whereHas('contacts', function ($subQuery) use ($contact) {
                        $subQuery->where('contact_id', $contact);
                    });
                })
                ->pluck('name', 'id');
        }

        // Super admin flow: fetch all organizations grouped by team name
        return static::select(['id', 'team_id', 'name'])
            ->with('team')
            ->when($contact, function ($query) use ($contact) {
                $query->whereHas('contacts', function ($subQuery) use ($contact) {
                    $subQuery->where('contact_id', $contact);
                });
            })
            ->get()
            ->groupBy(function ($organization) {
                return $organization->team?->name ? 'Team '.$organization->team->name : 'No Team';
            })
            ->map(function ($group) {
                return $group->pluck('name', 'id');
            });
    }
}
