<?php

namespace App\Filament\Tables\Actions\User;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;

class EnableUserAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'enableUserAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tooltip(__('user.Enable User'))
            ->icon('heroicon-m-hand-thumb-up')
            ->iconButton()
            ->color('success')
            ->requiresConfirmation()
            ->label(__('user.Enable User'))
            ->action(function (User $user): void {
                $user->enabled = true;
                $user->save();

                Notification::make()
                    ->success()
                    ->title(__('Success!'))
                    ->body(__('user.User has been Enabled'))
                    ->send();
            })
            ->visible(function (User $user) {
                return ! $user->enabled && auth()->user()->hasRole(['super_admin']) && auth()->user()->getKey() !== $user->getKey();
            });
    }
}
