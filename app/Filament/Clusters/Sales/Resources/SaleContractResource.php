<?php

namespace App\Filament\Clusters\Sales\Resources;

use App\Filament\Clusters\Products\Resources\TaxResource;
use App\Filament\Clusters\Sales;
use App\Filament\Clusters\Sales\Resources\SaleContractResource\Pages;
use App\Filament\Clusters\Sales\Resources\SaleContractResource\RelationManagers;
use App\Filament\Exports\SaleContractExporter;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\SaleContract\GenerateProjectAction;
use App\Filament\Tables\Actions\SaleContract\RenewContractAction;
use App\Libs\FormService;
use App\Libs\GenerateService;
use App\Libs\ProductService;
use App\Libs\UserService;
use App\Libs\WorkflowService;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Product\Product;
use App\Models\Product\Tax;
use App\Models\Sale\SaleContract;
use App\Models\Sale\SaleContractModel;
use App\Models\Sale\SaleContractStatus;
use App\Services\ContractService;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SaleContractResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected const VALID_UNTIL_LABEL = 'sale-contract.Valid Until';

    protected const VALID_FROM_LABEL = 'sale-contract.Valid From';

    protected const CONTRACT_MODEL_ID_PATH = '../../contract_model_id';

    protected static ?string $model = SaleContract::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = Sales::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['contact.full_name', 'organization.name', 'description'];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => WorkflowService::getCssStatus($record))
            ->defaultSort('acceptance_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaleContracts::route('/'),
            'create' => Pages\CreateSaleContract::route('/create'),
            'view' => Pages\ViewSaleContract::route('/{record}'),
            'edit' => Pages\EditSaleContract::route('/{record}/edit'),
            'activities' => Pages\ListSaleContractActivities::route('/{record}/activities'),
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
                                Forms\Components\Select::make('contract_model_id')
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        self::populateFieldsAfterModelSelect($set, $state);
                                    })
                                    ->label(__('sale-contract.Model'))
                                    ->options(fn (Get $get) => SaleContractModel::getOptionsForSelect($get('contract_model_id')))
                                    ->searchable(['name'])
                                    ->createOptionUsing(function (array $data): int {
                                        return SaleContractModel::create($data)->getKey();
                                    })
                                    ->createOptionForm(SaleContractModelResource::getFormsComponents())
                                    ->createOptionAction(
                                        fn (Action $action) => $action->modalWidth('7xl'),
                                    )
                                    ->preload()->live(),
                                Forms\Components\DatePicker::make('date')
                                    ->label(__('Date'))
                                    ->default(now())
                                    ->required(),
                            ])->columns(2),
                            Forms\Components\Grid::make('')->schema([
                                FormService::selectContactSale(Organization::class)
                                    ->disabled(fn () => request()->has('contactId')),
                                FormService::selectOrganization(Contact::class)
                                    ->disabled(fn () => request()->has('organizationId')),
                                Forms\Components\Select::make('contract_status_id')
                                    ->label(__('Status'))
                                    ->options(function ($record) {
                                        return WorkflowService::getWorkflowOptions(SaleContractStatus::class, $record?->contract_status_id);
                                    })
                                    ->default(function ($record) {
                                        return WorkflowService::getWorkFlowDefaultPermittedOption(SaleContractStatus::class, $record);
                                    })
                                    ->searchable(['status']),
                            ])->columns(3),
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\DatePicker::make('valid_from')
                                    ->label(__(self::VALID_FROM_LABEL))
                                    ->default(now())
                                    ->required(),
                                Forms\Components\DatePicker::make('valid_until')
                                    ->label(__(self::VALID_UNTIL_LABEL)),
                                Forms\Components\DatePicker::make('acceptance_date')
                                    ->label(__('sale-contract.Acceptance Date')),
                            ]),
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label(__('sale-contract.Description'))
                                    ->rows(6),
                                Forms\Components\Textarea::make('terms')
                                    ->label(__('sale-contract.Terms'))
                                    ->rows(6),
                            ])->columns(2),
                            Forms\Components\Grid::make('')->schema([
                                FormService::selectPaymentMethod(),
                                Forms\Components\Textarea::make('payment_conditions')
                                    ->label(__('sale-contract.Payment Conditions')),
                            ])->columns(2),
                            FormService::attachmentImageFileUploadFormSection(
                                'SaleContract',
                                __('Upload Documents'),
                                1,
                            ),
                        ]),

                        Forms\Components\Tabs\Tab::make(__('sale-contract.Products'))->schema([
                            Forms\Components\Section::make()->schema([
                                Forms\Components\Repeater::make('details')
                                    ->addActionLabel(__('sale-contract.Add Product'))
                                    ->label(__(''))->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            FormService::selectProduct()
                                                ->label(__('sale-contract.Product'))
                                                ->options(function (Get $get) {
                                                    return ProductService::getAllowedPricesInPricelist(SaleContractModel::class, $get(self::CONTRACT_MODEL_ID_PATH));
                                                })
                                                ->createOptionAction(function (Action $action): Action {
                                                    $action->modalWidth(MaxWidth::Full);
                                                    $action->after(function (Get $get) {
                                                        ProductService::createNewProductWithPrice(
                                                            $get('product_id'),
                                                            SaleContractModel::class,
                                                            $get(self::CONTRACT_MODEL_ID_PATH),
                                                        );
                                                    });

                                                    return $action;
                                                })
                                                ->afterStateUpdated(function (Forms\Set $set, $state, Get $get) {
                                                    if ($state) {
                                                        $product = Product::where('id', $state)->first();
                                                        $price = ContractService::getPrice($product->id, $get(self::CONTRACT_MODEL_ID_PATH));

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
                                                ->label(__('sale-contract.Code'))
                                                ->readOnly(),
                                        ]),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label(__('Name'))
                                                ->required(),
                                            Forms\Components\TextInput::make('description')
                                                ->label(__('sale-contract.Description')),
                                        ])->columns(2),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\TextInput::make('measurament_unit')
                                                ->label(__('sale-contract.Measurament Unit'))
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('quantity')
                                                ->label(__('sale-contract.Quantity'))
                                                ->numeric()
                                                ->required()
                                                ->afterStateUpdated(function (Forms\Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                            Forms\Components\TextInput::make('price')
                                                ->label(__('sale-contract.Price'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->required()
                                                ->afterStateUpdated(function (Forms\Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                        ])->columns(3),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\Toggle::make('is_discount_percentage')
                                                ->label(__('sale-contract.is % Discount'))
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
                                                        ->label(__('sale-contract.is % Discount'))
                                                        ->icon('heroicon-o-percent-badge')
                                                        ->color(fn (Get $get) => $get('is_discount_percentage') ? 'success' : 'danger')
                                                        ->visible(fn ($operation) => $operation == 'view' ? false : true)
                                                        ->action(function (Forms\Set $set, Get $get) {
                                                            $set('is_discount_percentage', $get('is_discount_percentage') ? false : true);
                                                            self::updateTotals($get, $set);
                                                        })
                                                )
                                                ->label(__('sale-contract.Discount'))
                                                ->afterStateUpdated(function (Forms\Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                            Forms\Components\TextInput::make('total_discount')
                                                ->label(__('sale-contract.Total Discount'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('Subtotal')
                                                ->label(__('sale-contract.Subtotal'))
                                                ->afterStateUpdated(function (Forms\Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                        ])->columns(3),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\Select::make('taxes')
                                                ->label(__('sale-contract.Taxes'))
                                                ->options(fn (Get $get) => Tax::getOptionsForSelect(collect($get('taxes'))->first()))
                                                ->createOptionUsing(function (array $data): int {
                                                    return Tax::create($data)->getKey();
                                                })
                                                ->createOptionForm(TaxResource::getFormsComponents())
                                                ->createOptionAction(
                                                    fn (Action $action) => $action->modalWidth('6xl'),
                                                )
                                                ->afterStateUpdated(function (Forms\Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->searchable()
                                                ->required()
                                                ->live(),
                                            Forms\Components\TextInput::make('total_taxes')
                                                ->label(__('sale-contract.Total Taxes'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('subtotal')
                                                ->label(__('sale-contract.Total'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                        ])->columns(3),
                                    ])
                                    ->cloneable()
                                    ->addable(function (Get $get) {
                                        return ! $get('contract_model_id') ? false : true;
                                    })
                                    ->afterStateUpdated(function (Forms\Set $set, Get $get) {
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
                    Forms\Components\Section::make()->schema([
                        Forms\Components\TextInput::make('total')
                            ->label(__('sale-contract.Total'))
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                            ->readOnly(),
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
                ->color('success')
                ->tooltip(fn ($record) => $record->uuid)
                ->sortable()
                ->searchable(),
            static::dateColumn('date', 'Date'),
            Tables\Columns\TextColumn::make('defaultModel.name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.contract-description', [
                        'documents' => $record->attachments->count(),
                        'description' => $record->description,
                        'model' => $record->defaultModel->name,
                        'payments' => $record->paymentReceipts?->count(),
                        'projects' => $record->projects?->count(),
                    ]);
                })
                ->label(__('Description'))
                ->wrap()
                ->sortable()
                ->searchable(),
            self::organizationContact(),
            Tables\Columns\SelectColumn::make('contract_status_id')
                ->options(function ($record) {
                    if (auth()->user()->hasRole('super_admin')) {
                        return WorkflowService::getAllowedNoTeamedStatuses(SaleContractStatus::class, $record);
                    } else {
                        return WorkflowService::getPermittedWorkflows(
                            'App\\Models\\Sale\\SaleContractStatus',
                            ['id', 'status', 'is_default', 'is_editable', 'to_process', 'is_processing', 'is_final_step', 'archived'],
                            'sorting',
                            'asc',
                            $record->contract_status_id
                        );
                    }
                })
                ->label(__('Status'))
                ->disabled(fn (SaleContract $record) => ! auth()->user()->can('update', $record)),
            static::dateColumn('acceptance_date', 'sale-contract.Acceptance Date'),
            static::dateColumn('valid_from', self::VALID_FROM_LABEL),
            static::dateColumn('valid_until', self::VALID_UNTIL_LABEL),
            Tables\Columns\TextColumn::make('total')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('sale-contract.Total'))
                ->description(function (SaleContract $record) {
                    return view('filament.tables.columns.contract-total', [
                        'record' => $record,
                        'currencyPrefix' => UserService::getCurrencyPrefix(),
                    ]);
                })
                ->summarize([
                    Sum::make()
                        ->label(__('sale-contract.Total Contracts'))
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
            static::organizationFilter(),
            static::contactFilter(),
            static::deadlineFilter(),
            Tables\Filters\Filter::make('validity')
                ->form([
                    Forms\Components\DatePicker::make('valid_from')->label(__(self::VALID_FROM_LABEL)),
                    Forms\Components\DatePicker::make('valid_until')->label(__(self::VALID_UNTIL_LABEL)),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['valid_from'],
                            fn (Builder $query, $validity): Builder => $query->whereDate('valid_from', '>=', $validity),
                        )
                        ->when(
                            $data['valid_until'],
                            fn (Builder $query, $validity): Builder => $query->whereDate('valid_until', '<=', $validity),
                        );
                })
                ->indicateUsing(function ($state) {
                    $indicator = $state['valid_from'] ? __(self::VALID_FROM_LABEL).': '.Carbon::parse($state['valid_from'])->toFormattedDateString().', ' : '';
                    $indicator .= $state['valid_until'] ? __(self::VALID_UNTIL_LABEL).': '.Carbon::parse($state['valid_until'])->toFormattedDateString() : '';

                    return $indicator;
                }),
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->options(fn () => WorkflowService::getAllowedNoTeamedStatuses(SaleContractStatus::class))
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query, $status): Builder => $query->where('contract_status_id', $status),
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
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                GenerateProjectAction::make('generate_project'),
                GenerateService::generateCommercialPdf('SaleContract', 'sale'),
                RenewContractAction::make('renew_contract'),
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
            ExportBulkAction::make()->exporter(SaleContractExporter::class),
            Tables\Actions\BulkActionGroup::make([
                self::changeRecordOwnership(),
                self::bulkAttachMember(),
                self::deleteBulkAction(),
                self::forceDeleteBulkAction(),
                self::restoreBulkAction(),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentReceiptRelationManager::class,
            RelationManagers\SaleQuoteRelationManager::class,
            RelationManagers\ProjectProjectRelationManager::class,
            RelationManagers\MemberRelationManager::class,
            RelationManagers\AttachmentRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    // This function updates totals based on the selected products and quantities
    public static function updateTotals($get, $set): void
    {
        $calculated = ContractService::calculateTotals(
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
            'manage_member',
            'generate_pdf',
            'export',
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.contracts');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.contracts');
    }

    protected static function populateFieldsAfterModelSelect(Forms\Set $set, $state)
    {
        self::resetFieldsAfterModelSelect($set);

        if (blank($state)) {
            return;
        }

        $contractModel = SaleContractModel::find($state);

        $date = now()->addDays((int) $contractModel?->validity_days);
        $set('valid_until', $date->toDateString());
        $set('description', $contractModel?->description);
        $set('terms', $contractModel?->terms);
        $set('payment_method_id', $contractModel?->payment_method_id);
    }

    protected static function resetFieldsAfterModelSelect(Forms\Set $set)
    {
        $set('details', null);
        $set('valid_until', null);
        $set('description', '');
        $set('terms', '');
        $set('payment_method_id', null);
    }
}
