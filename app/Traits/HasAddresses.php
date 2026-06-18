<?php

namespace App\Traits;

use App\Models\Address;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasAddresses
{
    /**
     * Get all addresses for this model.
     */
    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function defaultAddress(): MorphOne
    {
        // apply model Scope
        return $this->morphOne(Address::class, 'addressable')->default();
    }

    public function administrativeAddress(): MorphOne
    {
        // apply model Scope
        return $this->morphOne(Address::class, 'addressable')->administrative();
    }

    public function legalAddress(): MorphOne
    {
        // apply model Scope
        return $this->morphOne(Address::class, 'addressable')->legal();
    }

    public function billingAddress(): MorphOne
    {
        // apply model Scope
        return $this->morphOne(Address::class, 'addressable')->billing();
    }

    public function shippingAddress(): MorphOne
    {
        // apply Address model Scope
        return $this->morphOne(Address::class, 'addressable')->shipping();
    }
}
