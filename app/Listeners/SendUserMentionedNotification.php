<?php

namespace App\Listeners;

use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Kirschbaum\Commentions\Events\UserWasMentionedEvent;

class SendUserMentionedNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserWasMentionedEvent $event): void
    {
        $folder = '';
        $resource = Str::after($event->comment->commentable_type, 'App\\Models\\');
        $resource = explode('\\', $resource);

        switch (count($resource)) {
            case 1:
                $resource = 'admin/'.Str::plural(Str::snake($resource[0], '-'));
                break;

            case 2:
                $folder = Str::plural(Str::snake($resource[0], '-'));
                $resource = 'admin/'.$folder.'/'.Str::plural(Str::snake($resource[1], '-'));
                break;
            default:
                // code...
                break;
        }

        $url = URL::to('/'.$resource.'/'.$event->comment->commentable_id);

        Notification::make()
            ->title('Nuovo commento: '.getLabelFromModelClass($event->comment->commentable_type))
            ->body(auth()->user()->fullName.' - '.auth()->user()->email.' ti ha menzionato')
            ->success()
            ->actions([
                Action::make('view')
                    ->button()
                    ->url($url, shouldOpenInNewTab: true),
            ])
            ->sendToDatabase($event->user);
    }
}
