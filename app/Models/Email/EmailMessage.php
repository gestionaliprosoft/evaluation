<?php

namespace App\Models\Email;

use App\Models\Abstract\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailMessage extends BaseModel
{
    use SoftDeletes;

    protected $table = 'email_messages';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'emailable_type',
        'emailable_id',
        'user_id',
        'recipient_name',
        'recipient_email',
        'subject',
        'message',
        'email_template_id',
    ];

    public function emailable(): MorphTo
    {
        return $this->morphTo();
    }

    // Relationship to get the user who sent the email
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }
}
