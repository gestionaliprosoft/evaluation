<?php

namespace App\Models\Hrms;

use App\Models\Abstract\BaseModel;
use App\Models\Team;
use App\Models\User;
use App\Observers\Hrms\HrLeaveRequestObserver;
use App\Scopes\MemberScope;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

#[ObservedBy([HrLeaveRequestObserver::class])]
#[ScopedBy(TeamScope::class)]
#[ScopedBy(MemberScope::class)]
class HrLeaveRequest extends BaseModel
{
    use LogsActivity;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hr_leaves_requests';

    /**
     * The Picklists fields
     *
     * @var array
     */
    protected $picklists = [
        'status',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'team_id',
        'user_id',
        'hr_employee_id',
        'hr_leave_type_id',
        'date_from',
        'date_to',
        'annotations',
        'status',
        'status_reason',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(HrEmployee::class, 'hr_employee_id', 'id');
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(HrLeaveType::class, 'hr_leave_type_id', 'id');
    }

    public function getPicklists()
    {
        return $this->picklists;
    }
}
