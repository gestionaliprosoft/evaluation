<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Libs\UserService;
use App\Models\User;
use App\Services\TeamService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;
use STS\FilamentImpersonate\Pages\Actions\Impersonate;
use App\Filament\Tables\Actions\EmailMessage\SendEmailMessageHeaderAction;

class EditUser extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = UserResource::class;

    protected function getJollyField()
    {
        return $this->record->name.' '.$this->record->surname;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (! Arr::hasAll($data, ['team_id'])) {
            $data['team_id'] = auth()->user()->team_id;
        }

        // Resolve the service from the container
        $teamService = app(TeamService::class);
        $tenantId = $teamService->getTenantFromTeam($data['team_id']);
        $data['tenant_id'] = $tenantId;

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('transfer_owner_relationships')
                ->visible(auth()->user()->can('create', User::class))
                ->label(__('user.Transfer Owner Relationships'))
                ->fillForm(fn (User $user): array => [
                    'userId' => $user->getKey(),
                ])
                ->modalDescription(__('user.Select User to Transfer all Related Records'))
                ->form([
                    Select::make('user_id')
                        ->label(__('User'))
                        ->options(UserService::getAllowedUsers())
                        ->disableOptionWhen(function ($value, $record) {
                            return $value === $record->id;
                        })
                        ->required(),
                ])
                ->action(function (array $data, User $user): void {
                    $teamId = $user->team->id;
                    UserService::transferOwnerRelationships($teamId, $user->getKey(), $data['user_id']);

                    Notification::make()
                        ->success()
                        ->title(__('Success!'))
                        ->body(__('All Record(s) has been trasferred'))
                        ->send();

                    redirect()->route('filament.admin.resources.users.index');
                }),
            SendEmailMessageHeaderAction::make('send_email_message'),
            Actions\DeleteAction::make()
                ->fillForm(fn (User $user): array => [
                    'userId' => $user->getKey(),
                ])
                ->modalDescription(__('user.Delete & Select User to Transfer all Related Records'))
                ->form([
                    Select::make('user_id')
                        ->label('User')
                        ->options(fn (User $record) => UserService::getAllowedUsers($record))
                        ->disableOptionWhen(function ($value, $record) {
                            return $value === $record->id;
                        })
                        ->required(),
                ])
                ->action(function (array $data, User $user): void {
                    $teamId = $user->team->id;
                    UserService::transferOwnerRelationships($teamId, $user->getKey(), $data['user_id']);

                    // delete user from database
                    $user->delete();

                    Notification::make()
                        ->success()
                        ->title(__('User deleted successfully!'))
                        ->body(__('All Record(s) has been trasferred'))
                        ->send();

                    redirect()->route('filament.admin.resources.users.index');
                }),
        ];
    }

    protected function getActions(): array
    {
        return [
            Impersonate::make()->record($this->getRecord()),
        ];
    }
}
