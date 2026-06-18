<?php

namespace App\Filament\Clusters\Purchases\Resources;

use App\Filament\Clusters\Products\Resources\TaxResource;
use App\Filament\Clusters\Purchases;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\Pages;
use App\Filament\Clusters\Purchases\Resources\PurchaseOrderResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\PurchaseOrder\ProcessStockOrderAction;
use App\Libs\FormService;
use App\Libs\GenerateService;
use App\Libs\ProductService;
use App\Libs\UserService;
use App\Libs\WorkflowService;
use App\Models\Contact;
use App\Models\Product\Product;
use App\Models\Product\Tax;
use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderModel;
use App\Models\Purchase\PurchaseOrderStatus;
use App\Models\Vendor;
use App\Services\ModuleSettingService;
use App\Services\PurchaseOrderService;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PurchaseOrderResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected const ORDER_MODEL_ID_PATH = '../../order_model_id';

    protected static ?string $model = PurchaseOrder::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Purchases::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['contact.full_name', 'vendor.name', 'description'];
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('stockEntries'))
            ->recordClasses(fn (Model $record) => self::getCssStatus($record))
            ->defaultSort('date', 'desc')
            ->checkIfRecordIsSelectableUsing(
                fn (Model $record): bool => ! $record->stock_entries_count > 0 && ($record->isDefault() || $record->isEditable()),
            )
            ->poll(fn (ModuleSettingService $moduleSettingService) => $moduleSettingService->getModuleSettings('PurchaseOrders', 'tablePoll').'s'
            );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view' => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit' => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'activities' => Pages\ListPurchaseOrderActivities::route('/{record}/activities'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Forms\Components\Tabs::make('Tabs')->tabs([
                        Forms\Components\Tabs\Tab::make(__('Basic Informations'))->schema([
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\TextInput::make('number')
                                    ->visibleOn('view'),
                                Forms\Components\TextInput::make('uuid')
                                    ->label(__('Uuid'))
                                    ->visibleOn('view'),
                            ])->columns(2),
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\Select::make('order_model_id')
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        self::populateFieldsAfterModelSelect($set, $state);
                                    })
                                    ->label(__('purchase-order.Model'))
                                    ->options(fn (Get $get) => PurchaseOrderModel::getOptionsForSelect($get('order_model_id')))
                                    ->searchable(['name'])
                                    ->createOptionUsing(function (array $data): int {
                                        return PurchaseOrderModel::create($data)->getKey();
                                    })
                                    ->createOptionForm(PurchaseOrderModelResource::getFormsComponents())
                                    ->createOptionAction(
                                        fn (Action $action) => $action->modalWidth('7xl'),
                                    )
                                    ->preload()->live()->required(),
                                Forms\Components\DatePicker::make('date')
                                    ->label(__('Date'))
                                    ->default(now())
                                    ->required(),
                            ])->columns(2),
                            Forms\Components\Grid::make('')->schema([
                                FormService::selectContactPurchase(Vendor::class)
                                    ->disabled(fn () => request()->has('contactId')),
                                FormService::selectVendor(Contact::class)
                                    ->disabled(fn () => request()->has('vendorId')),
                            ])->columns(2),
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\Select::make('order_status_id')
                                    ->label(__('Status'))
                                    ->options(function ($record) {
                                        return WorkflowService::getWorkflowOptions(PurchaseOrderStatus::class, $record?->order_status_id);
                                    })
                                    ->default(function ($record) {
                                        return WorkflowService::getWorkFlowDefaultPermittedOption(PurchaseOrderStatus::class, $record);
                                    })
                                    ->searchable(['status']),
                                Forms\Components\DatePicker::make('estimated_shipping_date')
                                    ->label(__('purchase-order.Estimated Shipping Date'))
                                    ->required(),
                                FormService::selectPaymentMethod(),
                            ])->columns(3),
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label(__('purchase-order.Description'))
                                    ->rows(9),
                                Forms\Components\Textarea::make('terms')
                                    ->label(__('purchase-order.Terms'))
                                    ->rows(9),
                            ])->columns(2),
                            FormService::attachmentImageFileUploadFormSection(
                                'PurchaseOrder',
                                __('Upload Documents'),
                            ),
                        ]),

                        Forms\Components\Tabs\Tab::make(__('purchase-order.Products'))->schema([
                            Section::make()->schema([
                                Forms\Components\Repeater::make('details')
                                    ->addActionLabel(__('purchase-order.Add Product'))
                                    ->label(__(''))->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            FormService::selectProduct()
                                                ->label(__('purchase-order.Product'))
                                                ->options(function (Get $get) {
                                                    return ProductService::getAllowedPricesInPricelist(PurchaseOrderModel::class, $get(self::ORDER_MODEL_ID_PATH));
                                                })
                                                ->createOptionAction(function (Action $action): Action {
                                                    $action->modalWidth(MaxWidth::Full);
                                                    $action->after(function (Get $get) {
                                                        ProductService::createNewProductWithPrice(
                                                            $get('product_id'),
                                                            PurchaseOrderModel::class,
                                                            $get(self::ORDER_MODEL_ID_PATH),
                                                        );
                                                    });

                                                    return $action;
                                                })
                                                ->afterStateUpdated(function (Set $set, $state, Get $get, PurchaseOrderService $orderService) {
                                                    if ($state) {
                                                        $product = Product::where('id', $state)->first();
                                                        $price = $orderService->getPrice($product->id, $get(self::ORDER_MODEL_ID_PATH));

                                                        $set('internal_code', $product->internal_code);
                                                        $set('sku', $product->sku);
                                                        $set('name', $product->name);
                                                        $set('description', $product->description);
                                                        $set('measurament_unit', $product->measurament_unit);
                                                        $set('price', $price?->price);
                                                        $set('taxes', $price?->tax_id ? [$price->tax_id] : []);
                                                        $set('quantity', 1);
                                                    }

                                                    self::updateTotals($get, $set);
                                                }),
                                            Forms\Components\TextInput::make('sku')
                                                ->label(__('Sku'))
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('internal_code')
                                                ->label(__('purchase-order.Code'))
                                                ->readOnly(),
                                        ]),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label(__('Name'))
                                                ->required(),
                                            Forms\Components\TextInput::make('description')
                                                ->label(__('purchase-order.Description')),
                                        ])->columns(2),
                                        Forms\Components\Grid::make('')->schema(components: [
                                            Forms\Components\TextInput::make('measurament_unit')
                                                ->label(__('purchase-order.Measurament Unit'))
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('quantity')
                                                ->label(__('purchase-order.Quantity'))
                                                ->numeric()
                                                ->required()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                            Forms\Components\TextInput::make('price')
                                                ->label(__('purchase-order.Price'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->required()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                        ])->columns(3),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\Toggle::make('is_discount_percentage')
                                                ->label(__('purchase-order.is % Discount'))
                                                ->inline(false)
                                                ->hidden(),
                                            Forms\Components\TextInput::make('discount')
                                                ->prefix(function (Get $get, $operation): array|string|null {
                                                    if ($operation == 'view' && $get('discount')) {
                                                        return $get('is_discount_percentage') ? __('%') : __('Fix');
                                                    }

                                                    return null;
                                                })
                                                ->hintAction(
                                                    Action::make('setDiscountPercentage')
                                                        ->label(__('purchase-order.Discount %'))
                                                        ->icon('heroicon-o-percent-badge')
                                                        ->color(fn (Get $get) => $get('is_discount_percentage') ? 'success' : 'danger')
                                                        ->visible(fn ($operation) => $operation == 'view' ? false : true)
                                                        ->action(function (Set $set, Get $get) {
                                                            $set('is_discount_percentage', $get('is_discount_percentage') ? false : true);
                                                            self::updateTotals($get, $set);
                                                        })
                                                )
                                                ->label(__('purchase-order.Discount'))
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                            Forms\Components\TextInput::make('total_discount')
                                                ->label(__('purchase-order.Total Discount'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('Subtotal')
                                                ->label(__('purchase-order.Subtotal'))
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                        ])->columns(3),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\Select::make('taxes')
                                                ->label(__('purchase-order.Taxes'))
                                                ->options(fn (Get $get) => Tax::getOptionsForSelect(collect($get('taxes'))->first()))
                                                ->createOptionForm(TaxResource::getFormsComponents())
                                                ->createOptionUsing(function (array $data): int {
                                                    return Tax::create($data)->getKey();
                                                })
                                                ->createOptionAction(
                                                    fn (Action $action) => $action->modalWidth('6xl'),
                                                )
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->searchable()
                                                ->required()
                                                ->preload()
                                                ->live(),
                                            Forms\Components\TextInput::make('total_taxes')
                                                ->label(__('purchase-order.Total Taxes'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('subtotal')
                                                ->label(__('purchase-order.Total'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                        ])->columns(3),
                                    ])
                                    ->cloneable()
                                    ->addable(function (Get $get) {
                                        return ! $get('order_model_id') ? false : true;
                                    })
                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                        self::recalculateTotal($get, $set);
                                    })
                                    ->live()
                                    ->defaultItems(0),
                            ])->columns(1),
                        ]),
                    ]),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedFormSection(),
                    FormService::timestamps(),
                    Section::make()->schema([
                        Forms\Components\TextInput::make('total')
                            ->label(__('purchase-order.Total'))
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                            ->readOnly()
                            ->live(),
                    ])->columns(1),
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
            Tables\Columns\TextColumn::make('uuid')
                ->label(__('#Uuid'))
                ->size('xs')
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('number')
                ->label(__('Nr.'))
                ->sortable()
                ->searchable(),
            static::dateColumn('date', 'Date'),
            self::description()
                ->description(fn (PurchaseOrder $order) => __('purchase-order.Model').': '.$order->defaultModel->name),
            self::vendorContact(),
            Tables\Columns\SelectColumn::make('order_status_id')
                ->options(function ($record) {
                    if (auth()->user()->hasRole('super_admin')) {
                        return WorkflowService::getAllowedNoTeamedStatuses(PurchaseOrderStatus::class, $record);
                    } else {
                        return WorkflowService::getPermittedWorkflows(
                            PurchaseOrderStatus::class,
                            ['id', 'status', 'is_default', 'is_editable', 'to_process', 'is_processing', 'is_final_step', 'archived'],
                            'sorting',
                            'asc',
                            $record->order_status_id
                        );
                    }
                })
                ->label(__('Status'))
                ->disabled(fn (PurchaseOrder $record) => ! auth()->user()->can('update', $record)),
            static::dateColumn('estimated_sipping_date', 'purchase-order.Estimated Shipping Date'),
            Tables\Columns\TextColumn::make('total')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('purchase-order.Total'))
                ->description(function ($record) {
                    return view('filament.tables.columns.order-total', [
                        'record' => $record,
                        'currencyPrefix' => UserService::getCurrencyPrefix(),
                    ]);
                })
                ->summarize([
                    Sum::make()
                        ->label(__('purchase-order.Total Orders'))
                        ->money(auth()->user()->currency),
                ]),
            static::team(),
            static::user(),
            static::members(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::userFilter(),
            static::vendorFilter(),
            static::contactFilter(),
            static::deadlineFilter(),
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->options(fn () => WorkflowService::getAllowedNoTeamedStatuses(PurchaseOrderStatus::class))
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query, $date): Builder => $query->where('order_status_id', $data['value']),
                        );
                })
                ->preload(),
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
                ->beforeReplicaSaved(function ($replica, ModuleSettingService $moduleSettingService): void {
                    $replica['uuid'] = Str::uuid();
                    $replica['number_seq'] = PurchaseOrder::where('team_id', $replica['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
                    $replica['number'] = $moduleSettingService->getModuleSettings('Orders', 'number').$replica['number_seq'];
                })
                ->successRedirectUrl(fn (Model $replica): string => PurchaseOrderResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                GenerateService::generateCommercialPdf('PurchaseOrder', 'purchase'),
                ProcessStockOrderAction::make('processPhysicalStockIn'),
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
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchaseStockEntryRelationManager::class,
            RelationManagers\MemberRelationManager::class,
            RelationManagers\AttachmentRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    // This function updates totals based on the selected products and quantities
    public static function updateTotals($get, $set): void
    {
        $orderService = app(PurchaseOrderService::class);

        $calculated = $orderService->calculateTotals(
            $get('quantity'),
            $get('price'),
            $get('discount'),
            $get('is_discount_percentage'),
            $get('taxes')
        );
        $set('total_discount', $calculated['totalDiscount']);
        $set('Subtotal', $calculated['subtotalBeforeTax']);
        $set('total_taxes', $calculated['totalTaxes']);
        $set('subtotal', $calculated['subtotal']);

        $total = 0;
        foreach ($get('../../details') as $detail) {
            $total += $detail['subtotal'];
        }
        $set('../../total', $total);
    }

    // This function updates totals based on the duplicate or delete item
    public static function recalculateTotal($get, $set): void
    {
        $total = 0;
        foreach ($get('details') as $detail) {
            $total += (float) $detail['subtotal'];
        }

        $set('total', $total);
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
            'generate_pdf',
            'manage_member',
            'process_stock_in',
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.purchase_orders');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.purchase-orders');
    }

    protected static function getCssStatus($record): ?string
    {
        if ($record->isFinalStep()) {
            $cssClass = 'warning-row';
        } elseif ($record->isArchived()) {
            $cssClass = 'success-row';
        } else {
            return null;
        }

        return $cssClass;
    }

    public static function canEdit(Model $record): bool
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            return true;
        } else {
            $policyPermission = parent::canEdit($record);

            return $policyPermission && $record->isEditable();
        }
    }

    public static function canDelete(Model $record): bool
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            return true;
        } else {
            $policyPermission = parent::canDelete($record);

            return $policyPermission && $record->isEditable() && ! $record->stock_entries_count > 0;
        }
    }

    public static function canForceDelete(Model $record): bool
    {
        if (auth()->user()->isMainTenantSuperUser()) {
            return true;
        } else {
            $policyPermission = parent::canForceDelete($record);

            return $policyPermission && $record->isEditable() && ! $record->stock_entries_count > 0;
        }
    }

    protected static function populateFieldsAfterModelSelect(Set $set, $state)
    {
        self::resetFieldsAfterModelSelect($set);

        if (blank($state)) {
            return;
        }

        $purchaseOrderModel = PurchaseOrderModel::find($state);

        $date = now()->addDays((int) $purchaseOrderModel?->estimated_shipping_days);
        $set('estimated_shipping_date', $date->toDateString());
        $set('description', $purchaseOrderModel?->description);
        $set('terms', $purchaseOrderModel?->terms);
        $set('payment_method_id', $purchaseOrderModel?->payment_method_id);
    }

    protected static function resetFieldsAfterModelSelect(Set $set)
    {
        $set('details', null);
        $set('estimated_shipping_date', null);
        $set('description', '');
        $set('terms', '');
        $set('payment_method_id', null);
    }
}
