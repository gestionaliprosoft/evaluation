<?php

namespace App\Models;

use App\Scopes\ActivityScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ScopedBy(ActivityScope::class)]
class Activity extends \Spatie\Activitylog\Models\Activity
{
    protected $connection = 'mysql';
}
