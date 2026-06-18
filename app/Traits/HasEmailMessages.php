<?php

namespace App\Traits;

use App\Models\Email\EmailMessage;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasEmailMessages
{
    public function emails(): MorphMany
    {
        return $this->morphMany(EmailMessage::class, 'emailable');
    }

    /**
     * Static flag used by Global Scopes to instantly detect
     * that this model implements the emails relationship.
     */
    public static function hasEmailMessageRelation(): bool
    {
        return true;
    }
}
