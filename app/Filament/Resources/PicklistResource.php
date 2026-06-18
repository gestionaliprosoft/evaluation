<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PicklistResource\Pages;
use App\Filament\Resources\PicklistResource\RelationManagers;
use App\Libs\FormService;
use App\Libs\PicklistService;
use App\Models\Picklist;
use App\Services\DirectoryService;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PicklistResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = Picklist::class;

    protected static ?int $navigationSort = 5;

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
        return static::defineTable($table)->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPicklists::route('/'),
            'create' => Pages\CreatePicklist::route('/create'),
            'view' => Pages\ViewPicklist::route('/{record}'),
            'edit' => Pages\EditPicklist::route('/{record}/edit'),
            'activities' => Pages\ListPicklistActivities::route('/{record}/activities'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Forms\Components\Tabs::make('')->tabs([

                        Forms\Components\Tabs\Tab::make(__('Basic Informations'))->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\Select::make('module')
                                    ->label(__('picklists.Module'))
                                    ->options(fn (DirectoryService $directoryService) => $directoryService->getModelsNames())
                                    ->required()
                                    ->searchable()
                                    ->live()
                                    ->disabledOn('edit'),
                                Forms\Components\Select::make('name')
                                    ->label(__('Name'))
                                    ->required()
                                    ->disabledOn('edit')
                                    ->options(function (Get $get, Set $set, DirectoryService $directoryService, $operation, PicklistService $picklistService) {
                                        // get all exsisting picklists
                                        $existing = $picklistService->getTeamExistingPicklists($get('module'), $get('team_id'))->pluck('name', 'name');

                                        $modelNamespace = $directoryService->getModelNamespace(Str::ucfirst($get('module')));
                                        $model = new $modelNamespace;

                                        $picklists = method_exists($model, 'getPicklists') ? collect($model->getPicklists()) : collect();

                                        $options = [];
                                        if ($operation == 'create') {
                                            if ($picklists->count() == $existing->count()) {
                                                Notification::make()
                                                    ->title(__('picklists.All Picklist Already Created'))
                                                    ->info()
                                                    ->send();

                                                $set('module', '');
                                            } else {
                                                foreach ($picklists as $picklist) {
                                                    if (! Arr::exists($existing, $picklist)) {
                                                        $options[$picklist] = $picklist;
                                                    }
                                                }
                                            }
                                        } else {
                                            foreach ($picklists as $picklist) {
                                                $options[$picklist] = $picklist;
                                            }
                                        }

                                        return $options;
                                    })
                                    ->live()
                                    ->visible(fn (Get $get) => $get('module') == '' || ! $get('module') ? false : true
                                    ),
                            ])->columns(2),
                        ]),

                        Forms\Components\Tabs\Tab::make(__('picklists.Items'))->schema([
                            Forms\Components\Grid::make()->schema([
                                Forms\Components\Repeater::make('items')
                                    ->schema([
                                        Forms\Components\TextInput::make('value')
                                            ->label(__('picklists.Value'))
                                            ->maxLength(30)
                                            ->required(),
                                        Forms\Components\Checkbox::make('enabled')
                                            ->label(__('Enabled'))
                                            ->default(true)
                                            ->inline(false),
                                    ])
                                    ->columns(2)
                                    ->label('')
                                    ->visible(fn (Get $get) => $get('module') && $get('name')),
                            ])->columns(1),
                        ]),
                    ]),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedTeamSection(),
                    FormService::timestamps(),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('module')
                ->searchable()
                ->sortable()
                ->label(__('Module')),
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->label(__('Name')),
            static::team(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            Tables\Filters\SelectFilter::make('module')
                ->label(__('picklists.Module'))
                ->options(fn (DirectoryService $directoryService) => $directoryService->getModelsNames())
                ->searchable(),
            static::teamFilter(),
        ];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction(),
            self::editAction(),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->after(fn () => redirect(self::getUrl('index'))),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                self::changeRecordOwnership(),
                self::deleteBulkAction(),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.picklists');
    }
}
