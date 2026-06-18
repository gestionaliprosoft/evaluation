<?php

namespace App\Filament\Tables\Actions\User;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class DisableUserAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'disableUserAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tooltip(__('user.Disable User'))
            ->icon('heroicon-m-hand-thumb-down')
            ->iconButton()
            ->color('danger')
            ->requiresConfirmation()
            ->label(__('user.Disable User'))
            ->action(function (User $user): void {
                $user->enabled = false;
                $user->save();

                Notification::make()
                    ->success()
                    ->title(__('Success!'))
                    ->body(__('user.User has been Disabled'))
                    ->send();
            })
            ->visible(function (User $user) {
                return $user->enabled && auth()->user()->hasRole(['super_admin']) && auth()->user()->getKey() !== $user->getKey();
            });
    }
}
