<?php

namespace App\Filament\Clusters\Sales\Resources;

use App\Filament\Clusters\Products\Resources\TaxResource;
use App\Filament\Clusters\Sales;
use App\Filament\Clusters\Sales\Resources\SaleQuoteResource\Pages;
use App\Filament\Clusters\Sales\Resources\SaleQuoteResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\SaleQuote\GenerateContractAction;
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
use App\Models\Sale\SaleQuote;
use App\Models\Sale\SaleQuoteModel;
use App\Models\Sale\SaleQuoteStatus;
use App\Services\ModuleSettingService;
use App\Services\QuoteService;
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
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SaleQuoteResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected const QUOTE_MODEL_ID_PATH = '../../quote_model_id';

    protected static ?string $model = SaleQuote::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $cluster = Sales::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['contact.full_name', 'organization.name', 'description'];
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => WorkflowService::getCssStatus($record))
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSaleQuotes::route('/'),
            'create' => Pages\CreateSaleQuote::route('/create'),
            'view' => Pages\ViewSaleQuote::route('/{record}'),
            'edit' => Pages\EditSaleQuote::route('/{record}/edit'),
            'activities' => Pages\ListSaleQuoteActivities::route('/{record}/activities'),
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
                                Forms\Components\Select::make('quote_model_id')
                                    ->afterStateUpdated(function (Set $set, $state) {
                                        self::populateFieldsAfterModelSelect($set, $state);
                                    })
                                    ->label(__('sale-quote.Model'))
                                    ->options(fn (Get $get) => SaleQuoteModel::getOptionsForSelect($get('quote_model_id')))
                                    ->searchable(['name'])
                                    ->createOptionUsing(function (array $data): int {
                                        return SaleQuoteModel::create($data)->getKey();
                                    })
                                    ->createOptionForm(SaleQuoteModelResource::getFormsComponents())
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
                            ])->columns(2),
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\Select::make('quote_status_id')
                                    ->label(__('Status'))
                                    ->options(function ($record) {
                                        return WorkflowService::getWorkflowOptions(SaleQuoteStatus::class, $record?->quote_status_id);
                                    })
                                    ->default(function ($record) {
                                        return WorkflowService::getWorkFlowDefaultPermittedOption(SaleQuoteStatus::class, $record);
                                    })
                                    ->searchable(['status']),
                                Forms\Components\DatePicker::make('valid_until')
                                    ->label(__('sale-quote.Valid Until'))
                                    ->required(),
                                FormService::selectPaymentMethod(),
                            ])->columns(3),
                            Forms\Components\Grid::make('')->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label(__('sale-quote.Description'))
                                    ->rows(9),
                                Forms\Components\Textarea::make('terms')
                                    ->label(__('sale-quote.Terms'))
                                    ->rows(9),
                            ])->columns(2),
                            FormService::attachmentImageFileUploadFormSection(
                                'SaleQuote',
                                __('Upload Documents'),
                            ),
                        ]),

                        Forms\Components\Tabs\Tab::make(__('sale-quote.Products'))->schema([
                            Section::make()->schema([
                                Forms\Components\Repeater::make('details')
                                    ->addActionLabel(__('sale-quote.Add Product'))
                                    ->label(__(''))->schema([
                                        Forms\Components\Grid::make(3)->schema([
                                            FormService::selectProduct()
                                                ->label(__('sale-quote.Product'))
                                                ->options(function (Get $get) {
                                                    return ProductService::getAllowedPricesInPricelist(SaleQuoteModel::class, $get(self::QUOTE_MODEL_ID_PATH));
                                                })
                                                ->createOptionAction(function (Action $action): Action {
                                                    $action->modalWidth(MaxWidth::Full);
                                                    $action->after(function (Get $get) {
                                                        ProductService::createNewProductWithPrice(
                                                            $get('product_id'),
                                                            SaleQuoteModel::class,
                                                            $get(self::QUOTE_MODEL_ID_PATH),
                                                        );
                                                    });

                                                    return $action;
                                                })
                                                ->afterStateUpdated(function (Set $set, $state, Get $get, QuoteService $quoteService) {
                                                    if ($state) {
                                                        $product = Product::where('id', $state)->first();
                                                        $price = $quoteService->getPrice($product->id, $get(self::QUOTE_MODEL_ID_PATH));

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
                                                ->label(__('sale-quote.Code'))
                                                ->readOnly(),
                                        ]),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\TextInput::make('name')
                                                ->label(__('Name'))
                                                ->required(),
                                            Forms\Components\TextInput::make('description')
                                                ->label(__('sale-quote.Description')),
                                        ])->columns(2),
                                        Forms\Components\Grid::make('')->schema(components: [
                                            Forms\Components\TextInput::make('measurament_unit')
                                                ->label(__('sale-quote.Measurament Unit'))
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('quantity')
                                                ->label(__('sale-quote.Quantity'))
                                                ->numeric()
                                                ->required()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                            Forms\Components\TextInput::make('price')
                                                ->label(__('sale-quote.Price'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->required()
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                        ])->columns(3),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\Toggle::make('is_discount_percentage')
                                                ->label(__('sale-quote.is % Discount'))
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
                                                        ->label(__('sale-quote.Discount %'))
                                                        ->icon('heroicon-o-percent-badge')
                                                        ->color(fn (Get $get) => $get('is_discount_percentage') ? 'success' : 'danger')
                                                        ->visible(fn ($operation) => $operation == 'view' ? false : true)
                                                        ->action(function (Set $set, Get $get) {
                                                            $set('is_discount_percentage', $get('is_discount_percentage') ? false : true);
                                                            self::updateTotals($get, $set);
                                                        })
                                                )
                                                ->label(__('sale-quote.Discount'))
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })->live(),
                                            Forms\Components\TextInput::make('total_discount')
                                                ->label(__('sale-quote.Total Discount'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('Subtotal')
                                                ->label(__('sale-quote.Subtotal'))
                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                    self::updateTotals($get, $set);
                                                })
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                        ])->columns(3),
                                        Forms\Components\Grid::make('')->schema([
                                            Forms\Components\Select::make('taxes')
                                                ->label(__('sale-quote.Taxes'))
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
                                                ->label(__('sale-quote.Total Taxes'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                            Forms\Components\TextInput::make('subtotal')
                                                ->label(__('sale-quote.Total'))
                                                ->prefix(UserService::getCurrencyPrefix())
                                                ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                                                ->readOnly(),
                                        ])->columns(3),
                                    ])
                                    ->cloneable()
                                    ->addable(function (Get $get) {
                                        return ! $get('quote_model_id') ? false : true;
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
                            ->label(__('sale-quote.Total'))
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
            /* Tables\Columns\TextColumn::make('defaultModel.name')
                ->label(__('sale-quote.Model'))
                ->wrap()
                ->sortable()
                ->searchable(), */
            self::description()
                ->description(fn (SaleQuote $saleQuote) => __('sale-quote.Model').': '.$saleQuote->defaultModel->name),
            self::organizationContact(),
            Tables\Columns\SelectColumn::make('quote_status_id')
                ->options(function ($record) {
                    if (auth()->user()->hasRole('super_admin')) {
                        return WorkflowService::getAllowedNoTeamedStatuses(SaleQuoteStatus::class, $record);
                    } else {
                        return WorkflowService::getPermittedWorkflows(
                            'App\\Models\\Sale\\SaleQuoteStatus',
                            ['id', 'status', 'is_default', 'is_editable', 'to_process', 'is_processing', 'is_final_step', 'archived'],
                            'sorting',
                            'asc',
                            $record->quote_status_id
                        );
                    }
                })
                ->label(__('Status'))
                ->disabled(fn (SaleQuote $record) => ! auth()->user()->can('update', $record)),
            static::dateColumn('valid_until', 'sale-quote.Valid Until'),
            Tables\Columns\TextColumn::make('total')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('sale-quote.Total'))
                ->description(function ($record) {
                    return view('filament.tables.columns.quote-total', [
                        'record' => $record,
                        'currencyPrefix' => UserService::getCurrencyPrefix(),
                    ]);
                })
                ->summarize([
                    Sum::make()
                        ->label(__('sale-quote.Total Quotes'))
                        ->money(auth()->user()->currency),
                ]),
            Tables\Columns\TextColumn::make('belongs_to')
                ->state(function ($record) {
                    if ($record->contract) {
                        return getLabelFromModelClass(SaleContract::class);
                    }

                    return null;
                })
                ->tooltip(function ($record): string|View {
                    return $record ? view('filament.tables.columns.contract-belongs-to', ['record' => $record]) : '';
                })
                ->color('success')
                ->url(function ($record) {
                    $class = null;
                    $recordClass = null;

                    if ($record->contract) {
                        $class = $record->contract->getResourceClass();
                        $recordClass = $record->contract;
                    }

                    return $class ? $class::getUrl('edit', ['record' => $recordClass]) : '';
                })
                ->wrap()
                ->label(__('Belongs To')),
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
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->options(fn () => WorkflowService::getAllowedNoTeamedStatuses(SaleQuoteStatus::class))
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query, $date): Builder => $query->where('quote_status_id', $data['value']),
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
                    $replica['number_seq'] = SaleQuote::where('team_id', $replica['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
                    $replica['number'] = $moduleSettingService->getModuleSettings('SaleQuotes', 'number').$replica['number_seq'];
                })
                ->successRedirectUrl(fn (Model $replica): string => SaleQuoteResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                GenerateContractAction::make('generate_contract'),
                GenerateService::generateCommercialPdf('SaleQuote', 'sale'),
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
            RelationManagers\SaleContractRelationManager::class,
            RelationManagers\MemberRelationManager::class,
            RelationManagers\AttachmentRelationManager::class,
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    // This function updates totals based on the selected products and quantities
    public static function updateTotals($get, $set): void
    {
        $quoteService = app(QuoteService::class);

        $calculated = $quoteService->calculateTotals(
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
        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.quotes');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.quotes');
    }

    protected static function populateFieldsAfterModelSelect(Set $set, $state)
    {
        self::resetFieldsAfterModelSelect($set);

        if (blank($state)) {
            return;
        }

        $quoteModel = SaleQuoteModel::find($state);

        $date = now()->addDays((int) $quoteModel?->validity_days);
        $set('valid_until', $date->toDateString());
        $set('description', $quoteModel?->description);
        $set('terms', $quoteModel?->terms);
        $set('payment_method_id', $quoteModel?->payment_method_id);
    }

    protected static function resetFieldsAfterModelSelect(Set $set)
    {
        $set('details', null);
        $set('valid_until', null);
        $set('description', '');
        $set('terms', '');
        $set('payment_method_id', null);
    }
}
