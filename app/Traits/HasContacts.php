<?php

namespace App\Traits;

use App\Models\ModuleContact;

trait HasContacts
{
    /**
     * Get all  contacts.
     */
    public function contacts()
    {
        return $this->morphMany(ModuleContact::class, 'contactable');
    }
}
