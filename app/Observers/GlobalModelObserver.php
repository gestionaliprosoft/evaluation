<?php

namespace App\Observers;

use App\Services\CalendarEventService;
use Illuminate\Database\Eloquent\Model;

class GlobalModelObserver
{
    public function deleted(Model $model): void
    {
        CalendarEventService::handleCalendarEventsForModel('delete', $model);

        // add other services
    }

    public function restored(Model $model): void
    {
        CalendarEventService::handleCalendarEventsForModel('restore', $model);

        // add other services
    }

    public function forceDeleted(Model $model): void
    {
        CalendarEventService::handleCalendarEventsForModel('forceDelete', $model);

        // add other services
    }
}
