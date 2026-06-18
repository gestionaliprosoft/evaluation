<?php

namespace App\Models;

use App\Filament\Clusters\MasterData\Resources\ContactResource;
use App\Models\Abstract\BaseModel;
use App\Models\Domain\DomainDomain;
use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use App\Models\Sale\SaleQuote;
use App\Observers\ContactObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\Automationable;
use App\Traits\HasAddresses;
use App\Traits\HasMembers;
use App\Traits\HasTickets;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Traits\HasEmailMessages;

#[ObservedBy([ContactObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class Contact extends BaseModel
{
    use Automationable;
    use HasAddresses;
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
    protected $table = 'contacts';

    protected static $howManyFake = 5;

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'title',
        'source',
        'department',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'first_name',
        'last_name',
        'primary_phone',
        'secondary_phone',
        'mobile_phone',
        'primary_email',
        'secondary_email',
        'birth_date',
        'birth_place',

        'vat',
        'tax_id_code',

        'title',
        'source',
        'department',

        'description',
    ];

    /**
     * Get Resource class
     */
    public function getResourceClass(): string
    {
        return ContactResource::class;
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
     * Get all Organizations
     *
     * @return MorphToMany<Organization, Contact>
     */
    public function organizations()
    {
        return $this->morphedByMany(Organization::class, 'contactable', 'modules_contacts');
    }

    /**
     * Get all vendors.
     */
    public function vendors(): MorphToMany
    {
        return $this->morphedByMany(Vendor::class, 'contactable', 'modules_contacts');
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

    public function domains(): HasMany
    {
        return $this->hasMany(DomainDomain::class);
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
        return "{$this->first_name} {$this->last_name}";
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
                'first_name' => fake()->name(),
                'last_name' => fake()->lastName(),
                'primary_phone' => fake()->phoneNumber(),
                'secondary_phone' => fake()->phoneNumber(),
                'mobile_phone' => fake()->phoneNumber(),
                'primary_email' => fake()->companyEmail(),
                'secondary_email' => fake()->companyEmail(),
                'birth_date' => fake()->dateTimeBetween(50, 'now'),
                'birth_place' => fake()->country(),

                'vat' => null,
                'tax_id_code' => null,

                'title' => null,
                'source' => null,
                'department' => null,

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
     * Get the contact list formatted for Filament Select components,
     * taking into account the user's role, multi-team tenancy constraints,
     * ownership, memberships, and optional organization or vendor filters.
     */
    public static function getOptionsForSelect(int|string|null $organization = null, int|string|null $vendor = null): array|Collection
    {
        $user = auth()->user();

        // Prevent errors if no user is currently authenticated
        if (! $user) {
            return [];
        }

        // Base query with multi-team tenant boundaries and ownership
        $query = static::where('team_id', $user->team_id);

        if (! $user->hasRole(['super_admin'])) {
            $query->where(function ($subQuery) use ($user) {
                $subQuery->where('user_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                        $memberQuery->where('user_id', $user->id);
                    });
            });
        }

        // Filter by organization (morphedByMany) explicitly targeting organizations.id
        $query->when($organization, function ($subQuery) use ($organization) {
            $subQuery->whereHas('organizations', function ($orgQuery) use ($organization) {
                $orgQuery->where('organizations.id', $organization);
            });
        });

        // Filter by vendor (morphedByMany) explicitly targeting vendors.id
        $query->when($vendor, function ($subQuery) use ($vendor) {
            $subQuery->whereHas('vendors', function ($vendorQuery) use ($vendor) {
                $vendorQuery->where('vendors.id', $vendor);
            });
        });

        // Format data based on role for Filament
        if (! $user->hasRole(['super_admin'])) {
            return $query->get()->mapWithKeys(function ($contact) {
                return [$contact->id => $contact->full_name ?? "{$contact->first_name} {$contact->last_name}"];
            });
        }

        // Super Admin: Group by team name
        return $query->with('team')
            ->get()
            ->groupBy(function ($contact) {
                return $contact->team?->name ? 'Team '.$contact->team->name : 'No Team';
            })
            ->map(function ($group) {
                return $group->mapWithKeys(function ($contact) {
                    return [$contact->id => $contact->full_name ?? "{$contact->first_name} {$contact->last_name}"];
                });
            });
    }
}
