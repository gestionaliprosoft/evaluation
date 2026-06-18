<?php

namespace App\Models\Domain;

use App\Filament\Clusters\Domains\Resources\DomainDomainResource;
use App\Models\Abstract\BaseModel;
use App\Models\Contact;
use App\Models\Team;
use App\Models\User;
use App\Observers\Domains\DomainDomainObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\Automationable;
use App\Traits\HasMembers;
use App\Traits\HasOptionalEnabledScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([DomainDomainObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class DomainDomain extends BaseModel
{
    use Automationable;
    use HasMembers;
    use HasOptionalEnabledScope;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'domains';

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
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
        'contact_id',
        'provider_id',
        'hosting_id',
        'name',
        'type',
        'redirect_url',
        'username',
        'password',
        'email',
        'ns1',
        'ns2',
        'ip1',
        'ip2',
        'expire_date',
        'authinfo',
        'organization_username',
        'organization_password',
        'legal_username',
        'legal_password',
        'id_customer',
        'annotations',
        'enabled',
        'domainable_id',
        'domainable_type',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Get Resource class
     */
    public function getResourceClass(): string
    {
        return DomainDomainResource::class;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(DomainProvider::class);
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(DomainHosting::class);
    }

    /**
     * Get the parent domainable model
     */
    public function domainable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getPicklists()
    {
        return $this->picklists;
    }

    /**
     * Summary of seedRecords (dont't add team_id, user_id fields, will be added by seeder)
     *
     * @return Collection>
     */
    public function seedRecords(): Collection
    {
        return collect([
            [
                'provider_id' => 1,
                'name' => 'dominiotest1.com',
                'username' => 'dominiotest1_username',
                'password' => Crypt::encryptString('password'),
                'email' => 'dominiotest1@email.it',
                'expire_date' => now()->addDays(29),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_id' => 2,
                'name' => 'dominiotest2.com',
                'username' => 'dominiotest2_username',
                'password' => Crypt::encryptString('password'),
                'email' => 'dominiotest2@email.it',
                'expire_date' => now()->addDays(29),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'provider_id' => 2,
                'name' => 'dominiotest3.com',
                'username' => 'dominiotest3_username',
                'password' => Crypt::encryptString('password'),
                'email' => 'dominiotest3@email.it',
                'expire_date' => now()->addDays(29),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Get the complete domain list formatted for Filament Select components,
     * taking into account the user's role, multi-team tenancy, ownership, memberships,
     * forcing the inclusion of the currently selected record, and appending "(Disabled)" if it is inactive.
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
                    // Standard restriction path: must be active, owned or membered
                    $query->where(function ($subQuery) use ($user) {
                        $subQuery->onlyEnabled()
                            ->where(function ($innerQuery) use ($user) {
                                $innerQuery->where('user_id', $user->id)
                                    ->orWhereHas('members', function ($memberQuery) use ($user) {
                                        $memberQuery->where('user_id', $user->id);
                                    });
                            });
                    });

                    // Historical fallback path: force-include the disabled current record within tenant bounds
                    if ($currentId) {
                        $query->orWhere('id', $currentId);
                    }
                })
                ->get()
                ->mapWithKeys(function ($domain) use ($currentId) {
                    // Se l'ID corrisponde al record corrente ed è disabilitato (is_active/enabled è false), aggiunge (Disabled)
                    // Nota: Sostituisci 'is_active' o 'enabled' con il nome reale della tua colonna di stato se necessario
                    $isFieldEnabled = $domain->is_active ?? $domain->enabled ?? true;
                    $label = ($domain->id == $currentId && ! $isFieldEnabled)
                        ? $domain->name.' ('.__('Disabled').')'
                        : $domain->name;

                    return [$domain->id => $label];
                });
        }

        // Super admin flow: fetch domains grouped by team name
        return static::select(['id', 'team_id', 'name'])
            ->with('team')
            ->where(function ($query) use ($currentId) {
                // Apply enabled filter to the global list
                $query->onlyEnabled();

                // Force-include the current disabled historical record for super admin as well
                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->get()
            ->groupBy(function ($domain) {
                return $domain->team?->name ? 'Team '.$domain->team->name : 'No Team';
            })
            ->map(function ($group) use ($currentId) {
                return $group->mapWithKeys(function ($domain) use ($currentId) {
                    // Stessa logica di formattazione per il record disabilitato nel flusso Super Admin
                    $isFieldEnabled = $domain->is_active ?? $domain->enabled ?? true;
                    $label = ($domain->id == $currentId && ! $isFieldEnabled)
                        ? $domain->name.' ('.__('Disabled').')'
                        : $domain->name;

                    return [$domain->id => $label];
                });
            });
    }
}
