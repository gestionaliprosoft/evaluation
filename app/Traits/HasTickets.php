<?php

namespace App\Traits;

use App\Models\Ticket\Ticket;

trait HasTickets
{
    /**
     * Get all  tickets.
     */
    public function tickets()
    {
        return $this->morphMany(Ticket::class, 'ticketable');
    }
}
