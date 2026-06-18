<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use App\Observers\AutomationObserver;
use App\Scopes\TeamScope;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([AutomationObserver::class])]
#[ScopedBy(TeamScope::class)]
class Automation extends BaseModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'automations';

    protected $fillable = [
        'team_id',
        'name',
        'description',
        'target_model',
        'trigger',
        'enabled',
    ];

    protected $with = [
        'automationActions',
        'automationConditions',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function automationActions()
    {
        return $this->hasMany(AutomationAction::class);
    }

    public function automationConditions()
    {
        return $this->hasMany(AutomationCondition::class);
    }
}
