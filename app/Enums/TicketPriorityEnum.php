<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketPriorityEnum: string implements HasLabel
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::LOW => 'low',
            self::NORMAL => 'normal',
            self::HIGH => 'high',
        };
    }
}
