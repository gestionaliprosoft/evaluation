<?php

namespace App\Models\Hrms;

use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Observers\Hrms\HrPaySlipObserver;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HrPaySlipObserver::class])]
#[ScopedBy(TeamScope::class)]
class HrPaySlip extends BaseModel
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hr_pays_slips';

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'pay_slip_type',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'code',
        'name',
        'calculation_base',
        'is_percentage',
        'pay_slip_type',
        'rate',
        'annotations',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getPicklists()
    {
        return $this->picklists;
    }
}
