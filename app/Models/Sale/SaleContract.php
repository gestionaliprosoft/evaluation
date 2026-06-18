<?php

namespace App\Models\Sale;

use App\Filament\Clusters\Sales\Resources\SaleContractResource;
use App\Models\Abstract\BaseModel;
use App\Models\Accounting\PaymentMethod;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Product\Product;
use App\Models\Project\ProjectProject;
use App\Models\Team;
use App\Models\User;
use App\Observers\Sales\SaleContractObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\Automationable;
use App\Traits\HasMembers;
use App\Traits\HasPaymentReceipts;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([SaleContractObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class SaleContract extends BaseModel
{
    use Automationable;
    use HasMembers;
    use HasPaymentReceipts;
    use InteractsWithAttachments;
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contracts';

    protected static $howManyFake = 5;

    protected $fillable = [
        'number_seq',
        'team_id',
        'user_id',
        'uuid',
        'number',
        'contract_model_id',
        'quote_id',
        'date',
        'contact_id',
        'organization_id',
        'contract_status_id',
        'valid_from',
        'valid_until',
        'acceptance_date',
        'description',
        'terms',
        'payment_conditions',
        'payment_method_id',
        'total',
        'details',
    ];

    protected $casts = [
        'details' => 'array',
    ];

    protected string $displayField = 'description';

    /**
     * Get Resource class name
     */
    public function getResourceClass(): string
    {
        return SaleContractResource::class;
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
     * A Contract BelongsTo Contract Model
     */
    public function defaultModel(): BelongsTo
    {
        return $this->belongsTo(SaleContractModel::class, 'contract_model_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(SaleContractStatus::class, 'contract_status_id');
    }

    /**
     * A Contract BelongsTo Payment Methods
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    /**
     * A Contract BelongsTo Contact
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * A Contract belong to Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * A Contract HasMany Products
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * A Contract belongsTo Quotes
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(SaleQuote::class, 'quote_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(ProjectProject::class, 'contract_id', 'id');
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
                'number_seq' => $i + 1,
                'uuid' => Str::uuid(),
                'number' => 'CON-'.($i + 1),
                'contract_model_id' => 1,
                'quote_id' => $i + 1,
                'date' => now(),
                'contact_id' => null,
                'organization_id' => $i + 1,
                'contract_status_id' => 1,
                'valid_from' => now()->addDays(30),
                'valid_until' => now()->addYear(),
                'acceptance_date' => now(),
                'description' => fake()->text(30),
                'terms' => fake()->text(30),
                'payment_conditions' => fake()->text(20),
                'payment_method_id' => 1,
                'total' => (($i + 1) * 100) * ($i + 1),
                'details' => json_encode([
                    [
                        'name' => 'Product '.($i + 1),
                        'price' => (($i + 1) * 100) * ($i + 1),
                        'taxes' => 0,
                        'Subtotal' => (($i + 1) * 100) * ($i + 1),
                        'discount' => null,
                        'quantity' => 1,
                        'subtotal' => (($i + 1) * 100) * ($i + 1),
                        'product_id' => 1,
                        'description' => 'Product '.($i + 1),
                        'total_taxes' => 0,
                        'internal_code' => null,
                        'total_discount' => 0,
                        'measurament_unit' => 'N.',
                        'is_discount_percentage' => false,
                    ],
                ]),
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
     * Get the complete contracts list formatted for Filament Select components,
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

        // Base query setup with eager loading needed for the labels
        $baseQuery = static::select(['id', 'team_id', 'number', 'date', 'organization_id', 'user_id'])
            ->with(['team', 'organization']);

        // Standard user flow: filter by team, ownership, or explicit membership
        if (! $user->hasRole(['super_admin'])) {
            return $baseQuery->where('team_id', $user->team_id)
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
                ->get()
                ->mapWithKeys(function ($contract) {
                    $label = $contract->organization?->name.', Nr. '.$contract->number.', Data: '.$contract->date;

                    return [$contract->id => $label];
                });
        }

        // Super admin flow: fetch contracts grouped by team name
        return $baseQuery->where(function ($query) use ($currentId) {
            // Force-include the current historical record for super admin as well
            if ($currentId) {
                $query->orWhere('id', $currentId);
            }
        })
            ->get()
            ->groupBy(function ($contract) {
                return $contract->team?->name ? 'Team '.$contract->team->name : 'No Team';
            })
            ->map(function ($group) {
                return $group->mapWithKeys(function ($contract) {
                    $label = $contract->organization?->name.', Nr. '.$contract->number.', Data: '.$contract->date;

                    return [$contract->id => $label];
                });
            });
    }
}
