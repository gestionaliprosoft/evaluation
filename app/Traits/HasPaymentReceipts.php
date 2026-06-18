<?php

namespace App\Traits;

use App\Models\Accounting\PaymentReceipt;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasPaymentReceipts
{
    /**
     * Get all of paymentReceipts
     */
    public function paymentReceipts(): MorphMany
    {
        return $this->morphMany(PaymentReceipt::class, 'paymentable');
    }

    /**
     * Default getter for the payment total.
     * Override this method in your model if the column name is different.
     */
    public function getPaymentTotal(): float
    {
        return (float) ($this->total ?? 0);
    }

    /**
     * Default getter for the document number.
     */
    public function getPaymentNumber(): string
    {
        return (string) ($this->number ?? '');
    }
}
