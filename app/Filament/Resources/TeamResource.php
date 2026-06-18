<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Libs\FileService;
use App\Libs\FormService;
use App\Libs\PicklistService;
use App\Libs\UserService;
use App\Models\Team;
use App\Models\Tenant;
use App\Services\TeamService;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = Team::class;

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()->schema([
                    Forms\Components\Tabs::make()->tabs([
                        Forms\Components\Tabs\Tab::make(__('Basic Informations'))->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn () => ! auth()->user()->isMainTenantSuperUser())
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('business_name')
                                    ->label(__('team.Business Name'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('email')
                                    ->label(__('Email'))
                                    ->maxLength(255),
                            ])->columns(4),

                            Forms\Components\Grid::make()->schema([
                                Forms\Components\TextInput::make('vat')
                                    ->label(__('Vat Code'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('tax_id_code')
                                    ->label(__('Tax ID Code'))
                                    ->maxLength(255),
                                FormService::phoneField('phone', 'Phone'),
                                Forms\Components\Select::make('folder_quote')
                                    ->label(__('team.Folder Quote'))
                                    ->default(1)
                                    ->postfix('GB')
                                    ->options(PicklistService::getPicklistsByFieldName('folder_quote', 'team'))
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn () => ! auth()->user()->isMainTenantSuperUser())
                                    ->dehydrated(),
                            ])->columns(4),
                        ]),

                        Forms\Components\Tabs\Tab::make(__('Address'))->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\TextInput::make('address')
                                    ->label(__('Address'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('city')
                                    ->label(__('City'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('zip')
                                    ->label(__('Zip'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('country')
                                    ->label(__('Country'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('state')
                                    ->label(__('State'))
                                    ->maxLength(255),
                            ])->columns(5),
                        ]),

                        Forms\Components\Tabs\Tab::make('Stripe')->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\TextInput::make('test_stripe_key')
                                    ->label(__('Test Stripe Key'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('test_stripe_secret')
                                    ->label(__('Test Stripe Secret'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('live_stripe_key')
                                    ->label(__('Live Stripe Key'))
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('live_stripe_secret')
                                    ->label(__('Live Stripe Secret'))
                                    ->maxLength(255),
                                Forms\Components\Select::make('stripe_currency')
                                    ->label(__('Stripe Currency'))
                                    ->options(UserService::getCurrencyList())
                                    ->default('EUR')
                                    ->searchable(),
                                Forms\Components\Toggle::make('use_stripe_sandbox')
                                    ->label(__('Use Test mode'))
                                    ->inline(false)
                                    ->default(true),
                            ])->columns(2),
                        ]),
                    ]),
                ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)->recordUrl(false)
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
            'view' => Pages\ViewTeam::route('/{record}'),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable(),
            Tables\Columns\TextColumn::make('tenant')
                ->state(function (Team $team, TeamService $teamService) {
                    $tenantId = $teamService->getTenantFromTeam($team->getkey());

                    return Tenant::whereId($tenantId)->value('name');
                }),
            Tables\Columns\TextColumn::make('name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.team-name', [
                        'users' => $record->users->count(),
                        'name' => $record->name,
                    ]);
                })
                ->label(__('Name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('folder_quote')
                ->label(__('team.Folder Quote'))
                ->formatStateUsing(function (Team $record, FileService $fileService) {
                    $consumedQuota = $fileService->convertBytesToGb($fileService->getFolderSizeInBytes('team-'.$record->id));

                    if ($consumedQuota > $record->folder_quote) {
                        $consumedQuotaColor = 'danger';
                    } else {
                        $consumedQuotaColor = 'success';
                    }

                    return view('filament.tables.columns.team-folder-quote', [
                        'record' => $record,
                        'consumedQuota' => $consumedQuota,
                        'consumedQuotaColor' => $consumedQuotaColor,
                    ]);
                })
                ->alignCenter()
                ->searchable(),
            Tables\Columns\TextColumn::make('business_name')
                ->label(__('Business Name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('vat')
                ->label(__('Vat Code'))
                ->searchable(),
            Tables\Columns\TextColumn::make('tax_id_code')
                ->label(__('Tax ID Code'))
                ->searchable(),
            Tables\Columns\TextColumn::make('address')
                ->label(__('Address'))
                ->searchable(),
            Tables\Columns\TextColumn::make('city')
                ->label(__('City'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('zip')
                ->label(__('Zip'))
                ->searchable(),
            Tables\Columns\TextColumn::make('country')
                ->label(__('Country'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('state')
                ->label(__('State'))
                ->sortable()
                ->searchable(),
            self::phone('phone', 'Phone'),
            Tables\Columns\TextColumn::make('email')
                ->label(__('Email'))
                ->searchable(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction(),
            self::editAction(),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $eloquentQuery = parent::getEloquentQuery();

        if (auth()->user()->isMainTenantSuperUser()) {
            return $eloquentQuery;
        } else {
            // Resolve the service from the container
            $teamService = app(TeamService::class);
            $teamIds = $teamService->getTenantTeamsIds();

            return auth()->user()->hasRole(['super_admin'])
                ? $eloquentQuery->whereIn('id', $teamIds)
                : $eloquentQuery->whereNull('id');
        }
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.teams');
    }
}
