<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\User\ActivateUserAction;
use App\Filament\Tables\Actions\User\DisableUserAction;
use App\Filament\Tables\Actions\User\EnableUserAction;
use App\Libs;
use App\Models\User;
use App\Scopes\UserEnabledScope;
use App\Services\TeamService;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Hash;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Rawilk\FilamentPasswordInput\Password;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use App\Filament\Tables\Actions\EmailMessage\SendEmailMessageAction;

class UserResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'surname', 'email'];
    }

    public static function getNavigationBadge(): ?string
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            return static::getModel()::withoutGlobalScope(UserEnabledScope::class)->count();
        } else {
            if (auth()->user()->hasRole(['super_admin'])) {
                return static::getModel()::withoutGlobalScope(UserEnabledScope::class)->where('tenant_id', auth()->user()->tenant_id)->count();
            } else {
                return static::getModel()::where('id', auth()->user()->id)->count();
            }
        }
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => Libs\WorkflowService::getCssEnabledDisabled($record))
            ->recordUrl(false)
            ->modifyQueryUsing(function (Builder $query) {
                if (auth()->user()->isMainTenant()) {
                    return auth()->user()->hasRole(['super_admin'])
                        ? $query->withoutGlobalScope(UserEnabledScope::class)
                        : $query->whereId(auth()->user()->id);
                } else {
                    if (auth()->user()->hasRole(['super_admin'])) {
                        return $query->withoutGlobalScope(UserEnabledScope::class)->whereTenantId(auth()->user()->tenant_id);
                    } else {
                        return $query->whereId(auth()->user()->id);
                    }
                }
            })->defaultSort('tenant_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'activities' => Pages\ListUserActivities::route('/{record}/activities'),

        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Section::make()->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('surname')
                    ->label(__('Surname'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->unique(ignoreRecord: true)
                    ->readOnly(function ($operation) {
                        if (auth()->user()->isMainTenantSuperUser()) {
                            return false;
                        } else {
                            return $operation == 'edit' ? true : false;
                        }
                    })
                    ->label(__('Email'))
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->disabled(fn () => ! auth()->user()->isMainTenantSuperUser())
                    ->dehydrated(),
                Password::make('password')
                    ->hint(__('user.Leave blank, password will not be overwritten'))
                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Forms\Components\Grid::make()->schema([
                    Forms\Components\Select::make('team_id')
                        ->label(__('resources.TeamResource'))
                        ->options(fn (TeamService $teamService) => $teamService->getAllowedTenantedTeams())
                        ->preload()
                        ->searchable()
                        ->disabled(request()->has('teamId') || ! auth()->user()->isMainTenantSuperUser())
                        ->dehydrated()
                        ->default(function () {
                            if (request()->has('teamId')) {
                                return request()->input('teamId');
                            } else {
                                return isset(auth()->user()->team_id) ? auth()->user()->team_id : '';
                            }
                        })
                        ->hidden(! auth()->user()->hasRole(['super_admin'])),
                    Forms\Components\Select::make('roles')
                        ->label(__('Roles'))
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->searchable()
                        ->hidden(! auth()->user()->hasRole(['super_admin']))
                        ->disabled(fn () => ! auth()->user()->isMainTenantSuperUser())
                        ->dehydrated(),
                    Forms\Components\Toggle::make('disabled')
                        ->label(__('Disabled'))
                        ->inline(false)
                        ->disabled(fn () => ! auth()->user()->isMainTenantSuperUser()),
                ])->columns(3),
            ])->columns(2),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\ImageColumn::make('avatar_url')
                ->label('')
                ->circular(),
            Tables\Columns\TextColumn::make('name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.user-name', [
                        'tickets' => Libs\TicketService::getAllTicketsCount($record),
                        'name' => $record->name,
                    ]);
                })
                ->label(__('Name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('surname')
                ->label(__('Surname'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('email')
                ->label(__('Email'))
                ->searchable(),
            static::team(),
            Tables\Columns\TextColumn::make('Roles')
                ->label(__('Roles'))
                ->state(function ($record) {
                    $roles = $record->getRoleNames();

                    return view('filament.tables.columns.role-names', [
                        'roles' => $roles,
                    ]);
                }),
            Tables\Columns\TextColumn::make('tenant.name')
                ->label(__('resources.TenantResource')),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::teamFilter(),
            static::tenantFilter(),
        ];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction()->visible(fn (User $record): bool => $record->enabled),
            self::editAction()->visible(fn (User $record): bool => $record->enabled),
            Impersonate::make()
                ->redirectTo(route('filament.admin.pages.dashboard'))
                ->visible(fn (User $record): bool => $record->enabled && auth()->user()->isMainTenantSuperUser()),
            ActivateUserAction::make('activate_user'),
            DisableUserAction::make('disable_user'),
            EnableUserAction::make('enable_user'),
            Tables\Actions\DissociateAction::make()
                ->label(__(''))
                ->tooltip(__('Dissociate from current Tenant'))
                ->icon('heroicon-m-link-slash')
                ->modalHeading(__('Dissociate User'))
                ->modalSubheading(__('Chosen User will be Dissociated from current Tenant'))
                ->recordTitle(function ($record) {
                    return $record->name.' '.$record->surname.' (Team: '.$record->team->name.')';
                })
                ->recordTitleAttribute('surname')
                ->authorize('dissociate')
                ->visible(function (User $user) {
                    if ($user->enabled
                        && auth()->user()->isMainTenantSuperUser()
                        && auth()->user()->getKey() !== $user->getKey()
                        && $user->tenant
                    ) {
                        return true;
                    }

                    return false;
                }),
            Tables\Actions\ActionGroup::make([
                SendEmailMessageAction::make(),
                ActivityAction::make('activities'),
                static::seedTable(),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LeadsRelationManager::class,
            RelationManagers\OrganizationsRelationManager::class,
            RelationManagers\ContactsRelationManager::class,
            RelationManagers\QuotesRelationManager::class,
            RelationManagers\ContractsRelationManager::class,
            RelationManagers\ProjectsRelationManager::class,
            RelationManagers\TicketsRelationManager::class,
            RelationManagers\PasswordAccountsRelationManager::class,
            RelationManagers\ComicComicsRelationManager::class,
            RelationManagers\EmailMessageRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,

        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'force_delete',
            'force_delete_any',
            'restore',
            'restore_any',
            'replicate',
            'reorder',
            'dissociate',
            'send_email',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.users');
    }
}
