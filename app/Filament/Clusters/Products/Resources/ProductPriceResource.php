<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\ProductPriceResource\Pages;
use App\Filament\Clusters\Products\Resources\ProductPriceResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs;
use App\Libs\FormService;
use App\Models\Product\Product;
use App\Models\Product\ProductPrice;
use App\Models\Product\ProductPricelist;
use App\Models\Product\Tax;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class ProductPriceResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = ProductPrice::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = Products::class;

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
            'index' => Pages\ListProductPrices::route('/'),
            'create' => Pages\CreateProductPrice::route('/create'),
            'view' => Pages\ViewProductPrice::route('/{record}'),
            'edit' => Pages\EditProductPrice::route('/{record}/edit'),
            'activities' => Pages\ListProductPriceActivities::route('/{record}/activities'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        FormService::selectProduct()
                            ->afterStateUpdated(function (Get $get, Set $set, $operation, $state) {
                                // do not permit duplicate price into same Price List
                                if (
                                    $operation == 'create'
                                    && ProductPrice::hasPriceInPricelist($state, $get('product_pricelitsts_id'), $get('team_id'))
                                    && $state
                                ) {
                                    $set('product_id', null);

                                    Notification::make()
                                        ->title('Questo Prodotto è già presente nel Listino Prezzi selezionato')
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->label(__('product.Product'))
                            ->options(fn (Get $get) => Product::getOptionsForSelect($get('product_id')))
                            ->createOptionAction(
                                fn (Action $action) => $action->modalWidth(MaxWidth::Full),
                            )
                            ->suffixAction(fn (): Action => FormService::suffixExtendedSearch(
                                Product::class,
                                ['name', 'internal_code', 'serial_number', 'description'],
                                'product_id',
                            )),
                        Forms\Components\TextInput::make('price')
                            ->label(__('Price'))
                            ->prefix(Libs\UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2),
                        Forms\Components\Select::make('product_pricelists_id')
                            ->afterStateUpdated(function (Get $get, Set $set, $operation, $state) {
                                // do not permit duplicate price into same Price List
                                if (
                                    $operation == 'create'
                                    && ProductPrice::hasPriceInPricelist($get('product_id'), $state, $get('team_id'))
                                    && $get('product_id')
                                ) {
                                    $set('product_pricelists_id', null);

                                    Notification::make()
                                        ->title('Il Prodotto selezionato è già presente in questo Listino Prezzi')
                                        ->danger()
                                        ->send();
                                }
                            })
                            ->label(__('product.Pricelist'))
                            ->live()
                            ->required()
                            ->options(fn (Get $get) => ProductPricelist::getOptionsForSelect($get('product_pricelists_id')))
                            ->createOptionForm(ProductPricelistResource::getFormsComponents())
                            ->createOptionAction(
                                fn (Action $action) => $action->modalWidth('7xl'),
                            )
                            ->createOptionUsing(function (array $data): int {
                                if (! Arr::exists($data, 'user_id')) {
                                    $data['user_id'] = auth()->user()->id;
                                }

                                return ProductPricelist::create($data)->getKey();
                            })
                            ->searchable(),
                        Forms\Components\Select::make('tax_id')
                            ->label(__('Default Tax'))
                            ->options(fn (Get $get) => Tax::getOptionsForSelect($get('tax_id')))
                            ->createOptionForm(TaxResource::getFormsComponents())
                            ->createOptionAction(
                                fn (Action $action) => $action->modalWidth('6xl'),
                            )
                            ->searchable()
                            ->preload(),
                    ])->columns(2),
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
            Tables\Columns\TextColumn::make('product.name')
                ->searchable()
                ->sortable()
                ->label(__('product.Product')),
            Tables\Columns\TextColumn::make('price')
                ->prefix(Libs\UserService::getCurrencyPrefix())
                ->alignEnd()
                ->label(__('Price'))
                ->searchable(),
            Tables\Columns\TextColumn::make('productPricelist.name')
                ->searchable()
                ->sortable()
                ->label(__('product.Pricelist'))
                ->wrap(),
            Tables\Columns\TextColumn::make('tax.name')
                ->searchable()
                ->label(__('Default Tax')),
            static::team(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            SelectFilter::make('productPricelist')
                ->label(__('product.Pricelist'))
                ->searchable()
                // ->relationship('productPricelist', 'name')
                ->options(fn (Get $get) => ProductPricelist::getOptionsForSelect(null)),
            static::teamFilter(),
            static::trashedFilter(),
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
                ->successRedirectUrl(fn (Model $replica): string => ProductPriceResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->after(fn () => redirect(self::getUrl('index'))),
                self::forceDeleteAction(),
                ActivityAction::make('activities'),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.products');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.product_prices');
    }
}
