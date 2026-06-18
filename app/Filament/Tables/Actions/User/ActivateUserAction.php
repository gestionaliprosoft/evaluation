<?php

namespace App\Filament\Tables\Actions\User;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class ActivateUserAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'activateUserAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tooltip(__('user.Activate User'))
            ->icon('heroicon-m-bolt')
            ->iconButton()
            ->color('warning')
            ->requiresConfirmation()
            ->label(__('user.Activate User'))
            ->action(function (User $record) {
                $record->email_verified_at = now();
                $record->save();

                Notification::make()
                    ->success()
                    ->title(__('user.User Activated successfully!'))
                    ->send();
            })->visible(function (User $record) {
                return $record->email_verified_at == null && auth()->user()->hasRole(['super_admin']);
            });
    }
}
