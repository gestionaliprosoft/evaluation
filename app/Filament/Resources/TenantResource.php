<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenantResource\Pages;
use App\Filament\Resources\TenantResource\RelationManagers;
use App\Filament\Tables\Actions\Tenant\ExecuteMassMigrationAction;
use App\Filament\Tables\Actions\Tenant\ExecuteSingleMigrationAction;
use App\Filament\Tables\Actions\Tenant\RunCommandAction;
use App\Models\Tenant;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Crypt;
use Rawilk\FilamentPasswordInput\Password;

class TenantResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = Tenant::class;

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function getNavigationBadge(): ?string
    {
        return null;
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema(self::getFormComponents());
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return static::defineTable($table)->recordUrl(false)->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getFormComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Forms\Components\Section::make([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('Name'))
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->maxLength(255),
                            Forms\Components\TextInput::make('driver')
                                ->label(__('Driver'))
                                ->required()
                                ->default('mysql')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('url')
                                ->label(__('Url'))
                                ->url()
                                ->maxLength(255),
                        ])->columns(3),
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('host')
                                ->label(__('Host'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('port')
                                ->label(__('Port'))
                                ->required()
                                ->default('3306')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('database')
                                ->label(__('Database'))
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('username')
                                ->label(__('Username'))
                                ->required()
                                ->maxLength(255),
                            Password::make('password')
                                ->label(__('Password'))
                                ->required()
                                ->dehydrateStateUsing(fn (?string $state): string => Crypt::encryptString($state))
                                ->formatStateUsing(function (?string $state) {
                                    try {
                                        return Crypt::decryptString($state);
                                    } catch (DecryptException $e) {
                                        return $state;
                                    }
                                })
                                ->maxLength(255),
                        ])->columns(5),
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('unix_socket')
                                ->label(__('Unix Socket'))
                                ->maxLength(255),
                            Forms\Components\TextInput::make('charset')
                                ->label(__('Charset'))
                                ->required()
                                ->default('utf8mb4')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('collation')
                                ->label(__('Collation'))
                                ->required()
                                ->default('utf8mb4_unicode_ci')
                                ->maxLength(255),
                        ])->columns(3),
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('prefix')
                                ->label(__('Prefix'))
                                ->maxLength(255),
                            Forms\Components\TextInput::make('engine')
                                ->label(__('Engine'))
                                ->default(null),
                            Forms\Components\Toggle::make('prefix_indexes')
                                ->label(__('Prefix Indexes'))
                                ->required()
                                ->inline(false)
                                ->default(true),
                            Forms\Components\Toggle::make('strict')
                                ->label(__('Strict'))
                                ->required()
                                ->inline(false)
                                ->default(true),
                        ])->columns(4),
                    ])->columns(1),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Create New database')
                        ->description(__('Check to create new database & all support Tables (run migrations)'))
                        ->schema([
                            Forms\Components\Toggle::make('create_new_database')
                                ->label(__(''))
                                ->default(null),
                        ])
                        ->visibleOn('create'),
                    Forms\Components\Section::make('Update database')
                        ->description(__('Check to Update database & all support Tables (run migrate:fresh)'))
                        ->schema([
                            Forms\Components\Toggle::make('update_database')
                                ->label(__(''))
                                ->default(null),
                        ])
                        ->visibleOn('edit'),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable(),
            Tables\Columns\TextColumn::make('name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.tenant-name', [
                        'users' => $record->users->count(),
                        'name' => $record->name,
                    ]);
                })
                ->label(__('Name'))
                ->searchable(),
            Tables\Columns\TextColumn::make('driver')
                ->label(__('Driver'))
                ->searchable(),
            Tables\Columns\TextColumn::make('url')
                ->label(__('Url'))
                ->searchable(),
            Tables\Columns\TextColumn::make('host')
                ->label(__('Host'))
                ->searchable(),
            Tables\Columns\TextColumn::make('port')
                ->label(__('Port'))
                ->searchable(),
            Tables\Columns\TextColumn::make('database')
                ->label(__('Database'))
                ->searchable(),
            Tables\Columns\TextColumn::make('username')
                ->label(__('Username'))
                ->copyMessage(__(__('Copied to Clipboard')))
                ->copyMessageDuration(1500)
                ->searchable()
                ->visible(auth()->user()->isMainTenantSuperUser()),
            Tables\Columns\TextColumn::make('password')
                ->formatStateUsing(function (string $state) {
                    try {
                        return Crypt::decryptString($state);
                    } catch (DecryptException $e) {
                        return $state;
                    }
                })
                ->label(__('Password'))
                ->visible(auth()->user()->isMainTenantSuperUser())
                ->copyMessage(__(__('Copied to Clipboard')))
                ->copyMessageDuration(1500)
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
            self::editAction(),
            Tables\Actions\ReplicateAction::make()
                ->label('')
                ->tooltip(__('Replicate'))
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->name = '[NEW] '.$replica->name;
                })
                ->successRedirectUrl(fn (Model $replica): string => TenantResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\ActionGroup::make([
                ExecuteSingleMigrationAction::make('execute_single_migration'),
                ExecuteMassMigrationAction::make('execute_mass_migration'),
                RunCommandAction::make('run_command'),

                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->before(function ($record) {
                        // set no role for all users in that tenant
                        $users = $record->users;

                        foreach ($users as $user) {
                            $user->syncRoles([]);
                        }
                    })
                    ->after(fn () => redirect(self::getUrl('index'))),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        $eloquentQuery = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        if (auth()->user()->isMainTenantSuperUser()) {
            return $eloquentQuery;
        } else {
            return auth()->user()->hasRole(['super_admin'])
                ? $eloquentQuery->where('id', auth()->user()->tenant_id)
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
        return __('navigations.label.tenants');
    }
}
