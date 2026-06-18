<?php

namespace App\Filament\Clusters\Products\Resources;

use App\Filament\Clusters\Products;
use App\Filament\Clusters\Products\Resources\ProductResource\Pages;
use App\Filament\Clusters\Products\Resources\ProductResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs\FormService;
use App\Libs\PicklistService;
use App\Libs\WorkflowService;
use App\Models\Product\Product;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected const MEASUREMENT_UNIT_LABEL = 'product.Measurament Unit';

    protected static ?string $model = Product::class;

    protected static ?int $navigationSort = 5;

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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'activities' => Pages\ListProductActivities::route('/{record}/activities'),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            static::filePreviewTable('cover', __('Cover'), '80'),
            Tables\Columns\TextColumn::make('name')
                ->label(__('Name'))
                ->formatStateUsing(function ($record) {
                    $results = [];
                    $prices = $record->productPrices;
                    foreach ($prices as $price) {
                        $pricelist = $price->productPricelist;
                        if (isset($pricelist->id)) {
                            $results[] = ['id' => $pricelist->id, 'name' => $pricelist->name];
                        }
                    }

                    return view('filament.tables.columns.product-pricelists', [
                        'name' => $record->name,
                        'description' => $record->description,
                        'pricelists' => $results,

                    ]);
                })
                ->searchable()
                ->wrap(),
            Tables\Columns\TextColumn::make('internal_code')
                ->searchable()
                ->label(__('product.Internal Code')),
            Tables\Columns\TextColumn::make('serial_number')
                ->searchable()
                ->label(__('product.Serial Number')),
            Tables\Columns\TextColumn::make('sku')
                ->searchable()
                ->label(__('Sku')),
            Tables\Columns\TextColumn::make('stock_quantity')
                ->searchable()
                ->label(__('product.Stock Quantity'))
                ->description(function ($record) {
                    return $record->measurament_unit;
                }),
            Tables\Columns\TextColumn::make('type')
                ->searchable()
                ->wrap()
                ->label(__('Type')),
            Tables\Columns\TextColumn::make('category')
                ->searchable()
                ->label(__('Category')),
            Tables\Columns\ToggleColumn::make('enabled')
                ->label(__('Enabled'))
                ->disabled(fn (Product $record) => ! auth()->user()->can('update', $record)),
            static::team(),
            static::user(),
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
                ->successRedirectUrl(fn (Model $replica): string => ProductResource::getUrl('edit', [
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

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('name')
                                ->label(__('Name'))
                                ->required()
                                ->maxLength(150),
                            Forms\Components\Select::make('type')
                                ->label(__('Type'))
                                ->options(PicklistService::getPicklistsByFieldName('type', 'product'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('measurament_unit')
                                ->label(__(self::MEASUREMENT_UNIT_LABEL))
                                ->options(PicklistService::getPicklistsByFieldName('measurament_unit', 'product'))
                                ->searchable()
                                ->preload(),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('sku')
                                ->label(__('Sku')),
                            Forms\Components\TextInput::make('internal_code')
                                ->label(__('product.Internal Code'))
                                ->maxLength(150),
                            Forms\Components\TextInput::make('serial_number')
                                ->label(__('product.Serial Number'))
                                ->maxLength(150),
                        ]),
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('category')
                                ->label(__('Category'))
                                ->options(PicklistService::getPicklistsByFieldName('category', 'product'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\TextInput::make('description')
                                ->label(__('Description'))
                                ->maxLength(255),
                            Forms\Components\TextInput::make('stock_quantity')
                                ->label(__('product.Stock Quantity'))
                                ->numeric(),
                        ]),
                        Forms\Components\Grid::make()->schema([
                            Fieldset::make(__('product.Physical Quantities'))->schema([
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\TextInput::make('weight')
                                        ->label(__('product.Weight'))
                                        ->maxLength(50),
                                    Forms\Components\Select::make('weight_measurement_unit')
                                        ->label(__(self::MEASUREMENT_UNIT_LABEL))
                                        ->options(PicklistService::getPicklistsByFieldName('weight_measurement_unit', 'product'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\TextInput::make('size')
                                        ->label(__('product.Size'))
                                        ->maxLength(50),
                                    Forms\Components\Select::make('size_measurement_unit')
                                        ->label(__(self::MEASUREMENT_UNIT_LABEL))
                                        ->options(PicklistService::getPicklistsByFieldName('size_measurement_unit', 'product'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\TextInput::make('surface')
                                        ->label(__('product.Surface'))
                                        ->maxLength(50),
                                    Forms\Components\Select::make('surface_measurement_unit')
                                        ->label(__(self::MEASUREMENT_UNIT_LABEL))
                                        ->options(PicklistService::getPicklistsByFieldName('surface_measurement_unit', 'product'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\TextInput::make('volume')
                                        ->label(__('product.Volume'))
                                        ->maxLength(50),
                                    Forms\Components\Select::make('volume_measurement_unit')
                                        ->label(__(self::MEASUREMENT_UNIT_LABEL))
                                        ->options(PicklistService::getPicklistsByFieldName('volume_measurement_unit', 'product'))
                                        ->searchable()
                                        ->preload(),
                                ])->columns(4),
                            ]),
                        ])->columns(2),
                        Forms\Components\Toggle::make('enabled')
                            ->label(__('Enabled'))
                            ->default(true),
                        FormService::attachmentImageFileUploadFormSection(
                            'Product',
                            __('Upload Documents'),
                            null,
                            true,
                        ),
                    ]),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedFormSection(),
                    FormService::timestamps(),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PricesRelationManager::class,
            RelationManagers\AttachmentRelationManager::class,
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
            'download',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.products');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.products');
    }
}
