<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[ScopedBy(TeamScope::class)]
class Address extends BaseModel
{
    use SoftDeletes;

    protected $table = 'addresses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'team_id',
        'addressable_id',
        'addressable_type',
        'name',
        'type',
        'is_default',
        'is_administrative',
        'is_legal',
        'is_billing',
        'is_shipping',
        'address',
        'city',
        'zip',
        'country',
        'province',
        'state',
        'recipient_name',
        'phone',
        'email',
        'notes',
        'enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'is_administrative',
        'is_legal',
        'is_billing',
        'is_shipping',
        'enabled' => 'boolean',
    ];

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'type',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the parent addressable model
     */
    public function addressable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include default address.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope a query to only include administrative address.
     */
    public function scopeAdministrative($query)
    {
        return $query->where('is_administrative', true);
    }

    /**
     * Scope a query to only include legal address.
     */
    public function scopeLegal($query)
    {
        return $query->where('is_legal', true);
    }

    /**
     * Scope a query to only include billing address.
     */
    public function scopeBilling($query)
    {
        return $query->where('is_billing', true);
    }

    /**
     * Scope a query to only include shipping address.
     */
    public function scopeShipping($query)
    {
        return $query->where('is_shipping', true);
    }

    /**
     * Scope a query to filter by specific address type.
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the full address as a single formatted string.
     */
    public function getFullAddressAttribute(): string
    {
        return "{$this->address}, {$this->zip} {$this->city} ({$this->province})";
    }

    /**
     * Get the full address as a single formatted string with name.
     */
    public function getFullAddressWithNameAttribute(): string
    {
        return "{$this->name} - {$this->address}, {$this->zip} {$this->city} ({$this->province})";
    }

    public function getPicklists()
    {
        return $this->picklists;
    }

    /**
     * Restituisce le opzioni [id => label] degli addresses per un vendor.
     *
     * - Se $vendorId è null ritorna array vuoto.
     * - Restituisce solo addresses con enabled = true.
     * - Rispetta il vincolo team per utenti non super_admin.
     *
     * @return array<int,string>|Collection
     */
    public static function getOptionsForSelect(int|string|null $addressableId = null, ?string $addressableType = null): array|Collection
    {
        if (! $addressableId || ! $addressableType) {
            return [];
        }

        $user = auth()->user();

        $query = static::query()
            ->where('addressable_type', $addressableType)
            ->where('addressable_id', $addressableId)
            ->where('enabled', true)
            ->orderByDesc('is_default')
            ->orderByDesc('id');

        // Se l'utente non è super admin, limitiamo per team
        if ($user && ! $user->hasRole('super_admin')) {
            $query->where('team_id', $user->team_id);
        }

        return $query->get()
            ->mapWithKeys(fn (self $a) => [$a->id => $a->getFullAddressWithNameAttribute()]);
    }
}
