<?php

namespace App\Filament\Widgets;

use App\Libs\UserService;
use App\Models\CalendarEvent;
use App\Services\ModuleSettingService;
use App\Services\TeamService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Actions\ViewAction;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class Calendar extends FullCalendarWidget
{
    public Model|string|null $model = CalendarEvent::class;

    protected static ?int $sort = 11;

    protected int|string|array $columnSpan = 3;

    public function config(): array
    {
        return [
            'headerToolbar' => [
                'left' => 'prev,next today',
                'center' => 'title',
                'right' => 'dayGridMonth,timeGridWeek,timeGridDay,listYear',
            ],
            'titleFormat' => [
                'year' => 'numeric',
                'month' => 'short',
            ],
            'locale' => session()->get('locale'),
            'timezone' => auth()->user()->timezone,
        ];
    }

    public function fetchEvents(array $fetchInfo): array
    {
        return CalendarEvent::query()
            ->where('team_id', auth()->user()->team?->id)
            ->where('start_at', '>=', $fetchInfo['start'])
            ->where('end_at', '<=', $fetchInfo['end'])
            ->get()
            ->map(function (CalendarEvent $event): array {
                $resourceModel = '';
                if ($event?->resource && $event->resource_model) {
                    $resourceModel = Str::afterLast($event->resource_model, '\\');
                }

                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start' => $event->start_at,
                    'end' => $event->end_at,
                    'classNames' => [$resourceModel],
                    'backgroundColor' => '#0BDA51',
                    // 'url' => $linkedRecord ? $event?->resource::getUrl(name: 'view', parameters: ['record' => $event->record_id]) : '',
                    'shouldOpenUrlInNewTab' => true,
                    'displayEventEnd' => true,
                ];
            })
            ->all();
    }

    public function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('title')
                ->label(__('calendar.title'))
                ->required(),
            Forms\Components\Grid::make()->schema([
                Forms\Components\DateTimePicker::make('start_at')
                    ->label(__('calendar.start_at'))
                    ->required(),
                Forms\Components\DateTimePicker::make('end_at')
                    ->label(__('calendar.end_at'))
                    ->required(),
            ]),
            Forms\Components\Grid::make()->schema([
                Forms\Components\Select::make('team_id')
                    ->afterStateHydrated(function (Set $set) {
                        $set('team_id', auth()->user()->team_id);
                    })
                    ->label(__('resources.TeamResource'))
                    ->options(fn (TeamService $teamService) => $teamService->getAllowedTeams())
                    ->searchable()
                    ->hidden(! auth()->user()->hasRole(['super_admin'])),
                Forms\Components\Select::make('user_id')
                    ->afterStateHydrated(function (Set $set) {
                        $set('user_id', auth()->user()->id);
                    })
                    ->label(__('resources.UserResource'))
                    ->options(UserService::getAllowedUsers())
                    ->required()
                    ->searchable(),
            ]),
        ];
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mountUsing(
                    function (Forms\Form $form, array $arguments, ModuleSettingService $moduleSettingService) {
                        if (isset($arguments['end']) && is_string($arguments['end'])) {
                            $arguments['end'] = Carbon::parse($arguments['end']);
                        } elseif (isset($arguments['end']) && $arguments['end'] instanceof Carbon) {
                            //
                        } else {
                            $arguments['start'] = Carbon::parse(now());
                            $arguments['end'] = Carbon::parse(now())
                                ->addMinutes(
                                    $moduleSettingService->getModuleSettings('Calendars', 'slotDuration') ? (int) $moduleSettingService->getModuleSettings('Calendars', 'slotDuration') : 30
                                );
                        }
                        $form->fill([
                            'start_at' => $arguments['start'] ?? null,
                            'end_at' => $arguments['end'] ?? null,
                        ]);
                    }
                )
                ->visible(auth()->user()->can('create', CalendarEvent::class)),
        ];
    }

    protected function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->modalHeading(fn ($record) => $record->title)
            ->modalSubheading(function ($record): string {
                if ($record->resource_model && $record->record_id) {
                    $origin = __('calendar.origin').': '.__('resources.'.Str::afterLast($record->resource, '\\'));
                    $exists = $record?->resource_model::find($record?->record_id);
                    $subHeding = $exists ? $origin : $origin.' ('.__('calendar.no_more_in_database').')';
                } else {
                    $subHeding = '';
                }

                return $subHeding;
            })->visible(fn (CalendarEvent $record) => auth()->user()->can('view', $record));
    }

    protected function modalActions(): array
    {
        return [
            Action::make('origin')
                ->label(function ($record) {
                    $linkedRecord = ($record->resource_model && $record->record_id) ? ($record?->resource_model::find($record?->record_id) ?? null) : null;

                    return $linkedRecord ? __('calendar.view_origin') : '';
                })
                ->icon('heroicon-o-eye')
                ->url(function ($record) {
                    $linkedRecord = ($record->resource_model && $record->record_id) ? ($record?->resource_model::find($record?->record_id) ?? null) : null;

                    return $linkedRecord ? $record?->resource::getUrl(name: 'view', parameters: ['record' => $record->record_id]) : '';
                })->openUrlInNewTab()
                ->visible(function ($record) {
                    $linkedRecord = ($record->resource_model && $record->record_id) ? ($record?->resource_model::find($record?->record_id) ?? null) : null;

                    return $linkedRecord ? true : false;
                }),
            Actions\EditAction::make()
                ->modalHeading(fn ($record) => $record->title)
                ->modalSubheading(function ($record): string {
                    if ($record->resource_model && $record->record_id) {
                        $origin = __('calendar.origin').': '.__('resources.'.Str::afterLast($record->resource, '\\'));
                        $exists = $record?->resource_model::find($record?->record_id);
                        $subHeding = $exists ? $origin : $origin.' ('.__('calendar.no_more_in_database').')';
                    } else {
                        $subHeding = '';
                    }

                    return $subHeding;
                })->hidden(fn ($record) => $record->resource && $record->resource_model)
                ->visible(fn (CalendarEvent $record) => auth()->user()->can('update', $record)),
            Actions\DeleteAction::make()
                ->visible(fn (CalendarEvent $record) => auth()->user()->can('delete', $record)
                    && (
                        $record->resource === null
                        && $record->resource_model === null
                        && $record->record_id === null
                    )
                ),
        ];
    }

    public function eventDidMount(): string
    {
        return <<<'JS'
            function({ event, timeText, isStart, isEnd, isMirror, isPast, isFuture, isToday, el, view }) {
                el.setAttribute("x-tooltip", "tooltip");
                el.setAttribute("x-data", "{ tooltip: '"+event.title+" ("+event.classNames+")' }");
            }
        JS;
    }

    public static function canView(): bool
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            return true;
        } else {
            return auth()->user()->can('widget_Calendar');
        }
    }

    public function getPollingInterval(): ?string
    {
        $moduleSettingService = app(ModuleSettingService::class);

        return $moduleSettingService->getModuleSettings('Calendars', 'tablePoll').'s';
    }
}
