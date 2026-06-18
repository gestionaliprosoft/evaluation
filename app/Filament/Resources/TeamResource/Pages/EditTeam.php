<?php

namespace App\Filament\Resources\TeamResource\Pages;

use App\Filament\Resources\TeamResource;
use App\Models\Team;
use App\Services\TeamService;
use App\Traits\BaseSettings;
use App\Traits\CommonSettings;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditTeam extends EditRecord
{
    use BaseSettings;
    use CommonSettings;

    protected static string $resource = TeamResource::class;

    protected function getJollyField()
    {
        return $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->fillForm(fn (Team $team): array => [
                    'teamId' => $team->getKey(),
                ])
                ->modalDescription(__('Select Team to Transfer all Related Records'))
                ->form([
                    Select::make('team_id')
                        ->label('Team')
                        ->options(fn (TeamService $teamService) => $teamService->getAllowedTeams())
                        ->disableOptionWhen(function ($value, $record) {
                            $teamId = Team::where('id', $value)->first()->id;

                            return $record->id === $teamId;
                        })
                        ->required(),
                ])
                ->action(function (array $data, Team $team, TeamService $teamService): void {
                    // trasfer owner relationships documents
                    $teamService->transferOwnerRelationships($team->getkey(), $data['team_id']);

                    // delete team from database
                    $team->delete();

                    Notification::make()
                        ->success()
                        ->title(__('Team deleted successfully!'))
                        ->body(__('All Record(s) has been trasferred'))
                        ->send();

                    redirect()->route('filament.admin.resources.teams.index');
                }),
        ];
    }
}
