<?php

namespace App\Models\Accounting;

use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource;
use App\Models\Abstract\BaseModel;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Team;
use App\Models\User;
use App\Observers\Accountings\PaymentReceiptObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use App\Traits\HasMembers;
use App\Traits\InteractsWithAttachments;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

#[ObservedBy([PaymentReceiptObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class PaymentReceipt extends BaseModel
{
    use HasMembers;
    use InteractsWithAttachments;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'payments_receipts';

    protected static $howManyFake = 5;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'team_id',
        'user_id',
        'paymentable_id',
        'paymentable_type',
        'date',
        'description',
        'debit',
        'credit',
        'payment_method_id',
        'contact_id',
        'organization_id',
    ];

    /**
     * Get Resource class
     */
    public function getResourceClass(): string
    {
        return PaymentReceiptResource::class;
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
     * Get all of the models that own members.
     */
    public function paymentable()
    {
        return $this->morphTo();
    }

    /**
     * BelongsTo Payment Methods
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id', 'id');
    }

    /**
     * BelongsTo Contact
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * belong to Organization
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
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
                'paymentable_id' => $i + 1,
                'paymentable_type' => 'App\\Models\\Sale\\SaleContract',
                'date' => now(),
                'description' => 'CON-'.($i + 1),
                'debit' => (($i + 1) * 100) * ($i + 1),
                'credit' => 0,
                'payment_method_id' => 1,
                'contact_id' => null,
                'organization_id' => $i + 1,
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
