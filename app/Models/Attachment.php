<?php

namespace App\Models;

use App\Models\Abstract\BaseModel;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attachment extends BaseModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attachments';

    protected $fillable = [
        'team_id',
        'attachable_id',
        'attachable_type',
        'filename',
        'original_filename',
        'description',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
