<?php

use Carbon\Carbon;

if (! function_exists('getLabelFromModelClass')) {
    /**
     * Get Label From Model Class
     */
    function getLabelFromModelClass(?string $modelClass = null): string
    {
        return $modelClass
            ? __('resources.'.Str::afterLast($modelClass, '\\').'Resources')
            : '';
    }
}

if (! function_exists('formattedUserdate')) {
    /**
     * Format a date based on the authenticated user's date_format setting.
     */
    function formattedUserdate(mixed $date): ?string
    {
        if (! $date) {
            return null;
        }

        $format = auth()->user()?->date_format ?? 'd/m/Y';

        return Carbon::parse($date)->format($format);
    }
}
