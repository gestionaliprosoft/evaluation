<?php

namespace App\Filament\Resources;

use App\Enums\AutomationEnum;
use App\Filament\Resources\AutomationResource\Pages;
use App\Libs\AutomationService;
use App\Libs\FormService;
use App\Libs\UserService;
use App\Libs\WorkflowService;
use App\Models\Automation;
use App\Services\TeamService;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AutomationResource extends Resource
{
    use BaseSettings;

    protected const TARGET_MODEL_PATH = '../../target_model';

    protected static ?string $model = Automation::class;

    protected static ?int $navigationSort = 9;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => WorkflowService::getCssEnabledDisabled($record))
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAutomations::route('/'),
            'create' => Pages\CreateAutomation::route('/create'),
            'edit' => Pages\EditAutomations::route('/{record}/edit'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('name')
                                ->label('Name')
                                ->required(),
                            Forms\Components\TextInput::make('description')
                                ->label('Description'),
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\Select::make('target_model')
                                    ->label(__('Target Model'))
                                    ->required()
                                    ->options(AutomationService::getAutomationModelOptions())
                                    ->searchable()
                                    ->preload()
                                    ->afterStateUpdated(function (Set $set) {
                                        $set('trigger', '');
                                        $set('fields', '');
                                        $set('conditions', '');
                                        $set('actions', '');
                                    })->live(),
                                Forms\Components\Select::make('trigger')
                                    ->label(__('Trigger'))
                                    ->required()
                                    ->options(fn (Get $get): array => AutomationService::getAutomationTriggersOptions($get('target_model')))
                                    ->searchable()->preload(),
                                Forms\Components\Toggle::make('enabled')
                                    ->label(__('Enabled'))
                                    ->inline(false),
                            ])->columns(3),
                        ])->columns(2),
                        Section::make('Conditions')->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\Repeater::make('automationConditions')
                                    ->relationship()
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('field')
                                            ->options(fn (Get $get): array => AutomationService::getModelFillableOptions($get(self::TARGET_MODEL_PATH)))
                                            ->searchable()
                                            ->preload()->required(),
                                        Forms\Components\Select::make('condition')
                                            ->searchable()
                                            ->options(fn (Get $get): array => AutomationService::getAutomationConditionsOptions($get(self::TARGET_MODEL_PATH)))
                                            ->required(),
                                        Forms\Components\TextInput::make('condition_text')
                                            ->required(),
                                    ])->defaultItems(0)
                                    ->columns(3),
                            ])->columns(1),
                        ]),
                        Section::make('Actions')->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\Repeater::make('automationActions')
                                    ->relationship()
                                    ->addActionLabel(__('Add Action'))
                                    ->label('')
                                    ->schema([
                                        Forms\Components\Select::make('action')
                                            ->searchable()
                                            ->options(fn (Get $get): array => AutomationService::getAutomationActionsOptions($get(self::TARGET_MODEL_PATH)))
                                            ->required()->live(),
                                        Forms\Components\Select::make('recipient_type')
                                            ->label(__('Recipent Type'))
                                            ->visible(fn (Get $get): bool => $get('action') == AutomationEnum::SEND_NOTIFICATION || $get('action') == AutomationEnum::SEND_EMAIL)
                                            ->searchable()
                                            ->options([
                                                'roles' => 'Roles',
                                                'recipients' => 'Recipients',
                                            ])
                                            ->live()->required(),
                                        Forms\Components\Select::make('recipient_roles')
                                            ->multiple()
                                            ->visible(function (Get $get): bool {
                                                return $get('recipient_type') == 'roles';
                                            })
                                            ->label('Roles')
                                            ->options(UserService::getAllowedRoles())
                                            ->searchable()->required(),
                                        Forms\Components\Select::make('recipients')
                                            ->multiple()
                                            ->visible(function (Get $get): bool {
                                                return $get('recipient_type') == 'recipients' && ($get('action') == AutomationEnum::SEND_NOTIFICATION || $get('action') == AutomationEnum::SEND_EMAIL);
                                            })
                                            ->label('Recipients')
                                            ->searchable()
                                            ->options(UserService::getAllowedUsers())
                                            ->required(),
                                        Forms\Components\Select::make('recipient_teams')
                                            ->multiple()
                                            ->visible(fn (Get $get): bool => $get('recipient_type') == 'roles')
                                            ->label('Teams')
                                            ->default([auth()->user()->team->getKey()])
                                            ->searchable()
                                            ->options(fn (TeamService $teamService) => $teamService->getAllowedTeams())
                                            ->required(),
                                        Forms\Components\Checkbox::make('send_to_assigned_user')
                                            ->label(__('Also Notify to Assigned User'))
                                            ->helperText('Will be ignored if Assigned User is in Recipients')
                                            ->inline(false)
                                            ->default(false)
                                            ->visible(fn (Get $get): ?bool => $get('action') == AutomationEnum::SEND_NOTIFICATION),
                                        Forms\Components\Select::make('template')
                                            ->visible(fn (Get $get): bool => $get('action') == AutomationEnum::SEND_NOTIFICATION || $get('action') == AutomationEnum::SEND_EMAIL)
                                            ->label(__('Template'))
                                            ->searchable()
                                            ->options(AutomationService::getAutomationActionTemplates())
                                            ->required(),
                                        Forms\Components\Repeater::make('fields_map')
                                            ->label(__('Fields Map'))
                                            ->schema([
                                                Forms\Components\Select::make('calendar_field')
                                                    ->options([
                                                        'title' => 'Title',
                                                        'start_at' => 'Start At',
                                                        'end_at' => 'End At',
                                                    ])
                                                    ->label(__('Calendar Field'))
                                                    ->required(),
                                                Forms\Components\Select::make('target_model_field')
                                                    ->label(__('Target Model Field'))
                                                    ->options(fn (Get $get): array => AutomationService::getModelFillableOptions($get('../../../../target_model')))
                                                    ->preload()->required(),
                                            ])
                                            ->reorderable(false)
                                            ->visible(fn (Get $get) => $get('action') == AutomationEnum::CREATE_CALENDAR_REMINDER)
                                            ->columns(2),
                                        Forms\Components\Select::make('custom_function')
                                            ->visible(fn (Get $get): bool => $get('action') == AutomationEnum::EXECUTE_FUNCION)
                                            ->label(__('Function'))
                                            ->searchable()
                                            ->options(fn (Get $get) => AutomationService::getFunctions($get(self::TARGET_MODEL_PATH)))
                                            ->required(),
                                    ])->defaultItems(0)
                                    ->columns(3),
                            ])->columns(1),
                        ]),
                    ])->columns(1),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedTeamSection(),
                    FormService::timestamps(),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->description(fn ($record): ?string => $record->description)
                ->label(__('Name')),
            Tables\Columns\TextColumn::make('target_model')
                ->searchable()
                ->sortable()
                ->label(__('Target Model')),
            Tables\Columns\TextColumn::make('trigger')
                ->alignCenter()
                ->label(__('Trigger')),
            Tables\Columns\TextColumn::make('automationConditions')
                ->formatStateUsing(fn ($record): int => $record->automationConditions->count())
                ->alignCenter()
                ->label(__('Conditions')),
            Tables\Columns\TextColumn::make('automationActions')
                ->formatStateUsing(fn ($record): int => $record->automationActions->count())
                ->alignCenter()
                ->label(__('Actions')),
            Tables\Columns\ToggleColumn::make('enabled')
                ->label(__('Enabled')),
            static::team(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::teamFilter(),
            static::trashedFilter(),
        ];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::editAction(),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->after(fn () => redirect(self::getUrl('index'))),
                self::forceDeleteAction(),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                self::changeRecordOwnership(),
                self::deleteBulkAction(),
                self::forceDeleteBulkAction(),
                self::restoreBulkAction(),
            ]),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.automations');
    }
}
