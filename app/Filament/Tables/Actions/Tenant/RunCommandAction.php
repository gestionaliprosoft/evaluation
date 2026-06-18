<?php

namespace App\Filament\Tables\Actions\Tenant;

use App\Models\Tenant;
use App\Services\TenantService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;

class RunCommandAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'runCommandAction';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->visible(fn (Tenant $record): bool => auth()->user()->isMainTenantSuperUser())
            ->label('Run Command on this Tenant DB')
            ->icon('heroicon-o-command-line')
            ->color('warning')
            ->form([
                Select::make('command')
                    ->label(__('Select Command to execute on this Tenant'))
                    ->options(function () {
                        return collect(\Artisan::all())
                            ->mapWithKeys(fn ($cmd, $name) => [$name => $name])
                            ->sortKeys()
                            ->toArray();
                    })
                    ->searchable()
                    ->required(),
                TextInput::make('email')
                    ->label('Tenant Admin Email')
                    ->email()
                    ->required(),

                Textarea::make('arguments')
                    ->label(__('Arguments (optional, JSON)'))
                    ->placeholder('{"foo": "bar"}')
                    ->rows(3)
                    ->nullable(),

                Textarea::make('options')
                    ->label(__('Options (optional, JSON)'))
                    ->placeholder('{"--force": true}')
                    ->rows(3)
                    ->nullable(),
            ])
            ->requiresConfirmation()
            ->modalWidth(MaxWidth::ExtraLarge)
            ->modalDescription(__('Warning: this operation executes the selected Command on this Tenant!'))
            ->action(function (array $data, TenantService $tenantService, Tenant $record) {

                // Decode JSON (optional)
                $arguments = $data['arguments']
                    ? json_decode($data['arguments'], true)
                    : [];

                $options = $data['options']
                    ? json_decode($data['options'], true)
                    : [];

                if (json_last_error() !== JSON_ERROR_NONE) {
                    Notification::make()
                        ->title(__('Invalid JSON'))
                        ->body(__('Check the syntax of arguments or options'))
                        ->danger()
                        ->send();

                    return;
                }

                // Add required command arguments
                $arguments['name'] = $record->name;
                $arguments['email'] = $data['email'];

                // Delegate execution to the service
                $tenantService->executeSingleCommand(
                    $data['command'],
                    $arguments,
                    $options,
                    $record
                );
            });
    }
}
