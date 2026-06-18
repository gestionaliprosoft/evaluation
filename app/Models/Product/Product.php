<?php

namespace App\Models\Product;

use App\Filament\Clusters\Products\Resources\ProductResource;
use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Models\User;
use App\Observers\Products\ProductObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\HasOptionalEnabledScope;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([ProductObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class Product extends BaseModel
{
    use HasOptionalEnabledScope;
    use InteractsWithAttachments;
    use LogsActivity;
    use SoftDeletes;

    protected static $howManyFake = 10;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'measurament_unit',
        'type',
        'category',
        'weight_measurement_unit',
        'size_measurement_unit',
        'surface_measurement_unit',
        'volume_measurement_unit',
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
        'internal_code',
        'serial_number',
        'sku',
        'measurament_unit',
        'stock_quantity',
        'type',
        'category',
        'weight',
        'weight_measurement_unit',
        'size',
        'size_measurement_unit',
        'surface',
        'surface_measurement_unit',
        'volume',
        'volume_measurement_unit',
        'description',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function getResourceClass(): string
    {
        return ProductResource::class;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function ProductPrices()
    {
        return $this->hasMany(ProductPrice::class, 'product_id', 'id');
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
        $data = collect();

        for ($i = 0; $i < self::$howManyFake; $i++) {
            $data->add([
                'name' => 'Product '.$i + 1,
                'internal_code' => 'CODE- '.$i + 1,
                'serial_number' => fake()->randomNumber(9),
                'measurament_unit' => null,
                'stock_quantity' => ($i * 2),
                'type' => null,
                'category' => null,
                'weight' => null,
                'weight_measurement_unit' => null,
                'size' => null,
                'size_measurement_unit' => null,
                'surface' => null,
                'surface_measurement_unit' => null,
                'volume' => null,
                'volume_measurement_unit' => null,
                'description' => 'Product '.($i + 1).' long description',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $data;
    }

    protected static function getHowManyFake()
    {
        return self::$howManyFake;
    }

    /**
     * Get the complete product list formatted for Filament Select components,
     * taking into account the user's role, multi-team tenancy, ownership, dynamic memberships,
     * forcing the inclusion of the currently selected record, and appending "(Disabled)" if it is inactive.
     */
    public static function getOptionsForSelect(int|string|null $currentId = null): array|Collection
    {
        $user = auth()->user();

        // Prevent errors if no user is currently authenticated
        if (! $user) {
            return [];
        }

        // Standard user flow: filter by team, ownership, explicit membership, and enabled status
        if (! $user->hasRole(['super_admin'])) {
            return static::select(['id', 'team_id', 'name', 'enabled'])
                ->where('team_id', $user->team_id)
                ->where(function ($query) use ($user, $currentId) {
                    // Standard restriction path: must be enabled AND (owned or membered)
                    $query->where(function ($subQuery) use ($user) {
                        $subQuery->where('enabled', true)
                            ->where(function ($innerQuery) use ($user) {
                                $innerQuery->where('user_id', $user->id);

                                // Dynamic check: apply membership filter only if relation exists on the model
                                if (method_exists(static::class, 'members')) {
                                    $innerQuery->orWhereHas('members', function ($memberQuery) use ($user) {
                                        $memberQuery->where('user_id', $user->id);
                                    });
                                }
                            });
                    });

                    // Historical fallback path: force-include the disabled current record within tenant bounds
                    if ($currentId) {
                        $query->orWhere('id', $currentId);
                    }
                })
                ->get()
                ->mapWithKeys(function ($product) use ($currentId) {
                    // Se il record corrisponde a quello selezionato ed è disabilitato, aggiunge la dicitura
                    $label = ($product->id == $currentId && ! $product->enabled)
                        ? $product->name.' ('.__('Disabled').')'
                        : $product->name;

                    return [$product->id => $label];
                });
        }

        // Super admin flow: fetch products grouped by team name
        return static::select(['id', 'team_id', 'name', 'enabled'])
            ->with('team')
            ->where(function ($query) use ($currentId) {
                // Apply enabled filter to the global list
                $query->where('enabled', true);

                // Force-include the current disabled historical record for super admin as well
                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->get()
            ->groupBy(function ($product) {
                return $product->team?->name ? 'Team '.$product->team->name : 'No Team';
            })
            ->map(function ($group) use ($currentId) {
                return $group->mapWithKeys(function ($product) use ($currentId) {
                    // Stessa logica di formattazione condizionale applicata al flusso del Super Admin
                    $label = ($product->id == $currentId && ! $product->enabled)
                        ? $product->name.' ('.__('Disabled').')'
                        : $product->name;

                    return [$product->id => $label];
                });
            });
    }
}
