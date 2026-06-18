<?php

namespace App\Traits;

use App\Models\ModuleMember;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMembers
{
    /**
     * Get all of members.
     *
     * @return MorphMany<ModuleMember, static>
     */
    public function members(): MorphMany
    {
        return $this->morphMany(ModuleMember::class, 'memberable');
    }

    /**
     * Static flag used by Global Scopes to instantly detect
     * that this model implements the members relationship.
     */
    public static function hasMembersRelation(): bool
    {
        return true;
    }
}
