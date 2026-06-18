<?php

namespace App\Filament\Clusters\Tickets\Resources;

use App\Filament\Clusters\Tickets;
use App\Filament\Clusters\Tickets\Resources\TicketPackageResource\Pages;
use App\Filament\Clusters\Tickets\Resources\TicketPackageResource\RelationManagers;
use App\Filament\Clusters\Tickets\Resources\TicketPackageResource\RelationManagers\TicketInterventionRelationManager;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs\FormService;
use App\Libs\UserService;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketPackage;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class TicketPackageResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = TicketPackage::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = Tickets::class;

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
            'index' => Pages\ListTicketPackages::route('/'),
            'create' => Pages\CreateTicketPackage::route('/create'),
            'view' => Pages\ViewTicketPackage::route('/{record}'),
            'edit' => Pages\EditTicketPackage::route('/{record}/edit'),
            'activities' => Pages\ListTicketPackageActivities::route('/{record}/activities'),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('ticketCategory.name')
                ->searchable()
                ->wrap()
                ->label(__('Category')),
            Tables\Columns\TextColumn::make('name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.ticket-package-interventions', [
                        'name' => $record->name,
                        'interventions' => $record->ticketInterventions->count(),
                    ]);
                })
                ->searchable()
                ->label(__('Name')),
            Tables\Columns\TextColumn::make('description')
                ->searchable()
                ->label(__('Description')),
            Tables\Columns\TextColumn::make('price')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('Price')),
            Tables\Columns\TextColumn::make('ticket_quantity')
                ->label(__('ticket.Ticket Quantity'))
                ->alignRight(),
            Tables\Columns\TextColumn::make('duration')
                ->label(__('Duration').' ('.__('ticket.days').')')
                ->alignRight(),
            Tables\Columns\TextColumn::make('price_per_ticket')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('ticket.Price per Ticket')),
            Tables\Columns\TextColumn::make('tickets_cost_per_intervention')
                ->label(__('ticket.Tickets cost per Intervention'))
                ->alignRight(),
            Tables\Columns\CheckboxColumn::make('is_default')
                ->label(__('Default')),
            static::team(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::teamFilter(),
        ];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction(),
            self::editAction(),
            Tables\Actions\ReplicateAction::make()
                ->label('')
                ->tooltip(__('Replicate'))
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->name = '[NEW] '.$replica->name;
                })
                ->successRedirectUrl(fn (Model $replica): string => TicketPackageResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->after(fn () => redirect(self::getUrl('index'))),
                ActivityAction::make('activities'),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                self::deleteBulkAction(),
            ]),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\Select::make('ticket_category_id')
                            ->label(__('Category'))
                            ->options(fn (Get $get) => TicketCategory::getOptionsForSelect($get('ticket_category_id')))
                            ->searchable(['name'])
                            ->createOptionUsing(function (array $data): int {
                                return TicketCategory::create($data)->getKey();
                            })
                            ->createOptionForm(TicketCategoryResource::getFormsComponents())
                            ->required()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label(__('Description')),
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price'))
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2),
                        Forms\Components\TextInput::make('ticket_quantity')
                            ->numeric()
                            ->label(__('ticket.Ticket Quantity'))
                            ->required(),
                        Forms\Components\TextInput::make('duration')
                            ->hint(__('in days'))
                            ->numeric()
                            ->label(__('Duration'))
                            ->required(),
                        Forms\Components\TextInput::make('price_per_ticket')
                            ->label(__('ticket.Price per Ticket'))
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2),
                        Forms\Components\TextInput::make('tickets_cost_per_intervention')
                            ->numeric()
                            ->label(__('ticket.Tickets cost per Intervention'))
                            ->required(),
                        Forms\Components\Toggle::make('is_default')
                            ->label(__('Default'))
                            ->inline(false),
                    ])->columns(2),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::belongToTeam(),
                    FormService::timestamps(),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            TicketInterventionRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.tickets');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.ticket_packages');
    }
}
