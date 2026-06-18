<?php

namespace App\Filament\Tables\Actions;

use App\Models\Activity;
use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;

class ActivityAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activityAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->url(function ($record) {
            if (! $record) {
                return '#';
            }

            // DYNAMIC RESOURCE RETRIEVAL
            // Ask the Filament panel: "Which Resource manages this Model?"
            $resource = Filament::getCurrentPanel()
                ->getModelResource($record::class);

            // If no Resource is found (e.g., model not registered in Filament), prevent a crash
            if (! $resource) {
                return '#';
            }

            // Call getUrl() on the Resource, passing the record
            return $resource::getUrl('activities', ['record' => $record]);
        })
            ->label(__('Updates'))
            ->hidden(fn () => ! auth()->user()->can('viewAny', Activity::class));
    }
}
