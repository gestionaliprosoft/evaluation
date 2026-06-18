<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\ProductPricelistResource\Pages;
use App\Filament\Clusters\Products\Resources\ProductPricelistResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs\FormService;
use App\Libs\WorkflowService;
use App\Models\Product\ProductPricelist;
use App\Traits\BaseSettings;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductPricelistResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = ProductPricelist::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $cluster = Products::class;

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
            'index' => Pages\ListProductPricelists::route('/'),
            'create' => Pages\CreateProductPricelist::route('/create'),
            'view' => Pages\ViewProductPricelist::route('/{record}'),
            'edit' => Pages\EditProductPricelist::route('/{record}/edit'),
            'activities' => Pages\ListProductPricelistActivities::route('/{record}/activities'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->maxLength(150),
                        Forms\Components\Toggle::make('enabled')
                            ->label(__('Enabled'))
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedFormSection(),
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
            Tables\Columns\TextColumn::make('name')
                ->label(__('Name'))
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.count-products-badge', [
                        'name' => $record->name,
                        'productsCount' => count($record->productPrices),
                    ]);
                })
                ->searchable(),
            Tables\Columns\ToggleColumn::make('enabled')
                ->label(__('Enabled'))
                ->disabled(fn (ProductPricelist $record) => ! auth()->user()->can('update', $record)),
            static::team(),
            static::user(),
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
            self::viewAction(),
            self::editAction(),
            Tables\Actions\ReplicateAction::make()
                ->label('')
                ->tooltip(__('Replicate'))
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->name = '[NEW] '.$replica->name;
                })
                ->successRedirectUrl(fn (Model $replica): string => ProductPricelistResource::getUrl('edit', [
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
            RelationManagers\PricesRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.products');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.product_pricelists');
    }
}
