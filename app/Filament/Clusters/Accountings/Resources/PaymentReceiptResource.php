<?php

namespace App\Filament\Clusters\Accountings\Resources;

use App\Filament\Clusters\Accountings;
use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\Pages;
use App\Filament\Clusters\Accountings\Resources\PaymentReceiptResource\RelationManagers;
use App\Filament\Tables\Actions\PaymentReceipt\AddLinkedTransactionAction;
use App\Libs\FormService;
use App\Libs\UserService;
use App\Models\Accounting\PaymentReceipt;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\OrganizationDetail;
use App\Models\Project\ProjectProject;
use App\Models\Sale\SaleContract;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentReceiptResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected static ?string $model = PaymentReceipt::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Accountings::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['contact.full_name', 'organization.name', 'description'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->description;
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->defaultSort('date', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentReceipts::route('/'),
            'create' => Pages\CreatePaymentReceipt::route('/create'),
            'view' => Pages\ViewPaymentReceipt::route('/{record}'),
            'edit' => Pages\EditPaymentReceipt::route('/{record}/edit'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Group::make([
                    Section::make()->schema([
                        DatePicker::make('date')
                            ->label(__('Date'))
                            ->default(date('Y-m-d'))
                            ->required(),
                        TextInput::make('description')
                            ->required()
                            ->label(__('Description')),
                        FormService::selectContactSale(Organization::class)
                            ->disabled(fn (Get $get) => $get('paymentable_type') && ! auth()->user()->hasRole(['super_admin'])),
                        FormService::selectOrganization(Contact::class)
                            ->disabled(fn (Get $get) => $get('paymentable_type') && ! auth()->user()->hasRole(['super_admin']))
                            ->afterStateUpdated(function (Set $set, $state, $operation) {
                                if ($operation == 'create') {
                                    $paymentMethodId = $paymentMethodId = OrganizationDetail::where('organization_id', $state)->value('payment_method_id');
                                    $set('payment_method_id', $paymentMethodId);
                                }
                            }),
                        FormService::selectPaymentMethod(),
                        TextInput::make('paymentable_type')
                            ->formatStateUsing(fn ($state): array|string|null => $state ? getLabelFromModelClass($state) : '')
                            ->label(__('payment-receipt.Origin'))
                            ->disabled()
                            ->suffixAction(
                                Action::make('origin')
                                    ->icon('heroicon-m-arrow-right-start-on-rectangle')
                                    ->url(function ($record) {
                                        $originClass = $record?->paymentable_type ?? null;
                                        $class = $originClass ? (new $originClass)->getResourceClass() : null;

                                        return $record?->paymentable_id && $class ? $class::getUrl('edit', ['record' => $record?->paymentable_id]) : '';
                                    })
                                    ->tooltip(__('Go to Origin of this Payment Receipt'))
                                    ->visible(fn ($record, $context) => $record?->paymentable_id && $context == 'edit')
                            ),
                        TextInput::make('debit')
                            ->label(__('payment-receipt.Debit'))
                            ->required()
                            ->default(0)
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                            ->disabledOn('Edit')
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state > 0) {
                                    $set('credit', 0);
                                }
                            })->live(onBlur: true),
                        TextInput::make('credit')
                            ->label(__('payment-receipt.Credit'))
                            ->required()
                            ->default(0)
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2)
                            ->disabledOn('Edit')
                            ->afterStateUpdated(function (Set $set, $state) {
                                if ($state > 0) {
                                    $set('debit', 0);
                                }
                            })->live(onBlur: true),
                        FormService::attachmentImageFileUploadFormSection(
                            'PaymentReceipt',
                            __('Upload Documents'),
                        ),
                    ])->columns(2),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Group::make([
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
            Tables\Columns\TextColumn::make('uuid')
                ->label(__('#Uuid'))
                ->size('xs')
                ->toggleable(isToggledHiddenByDefault: true),
            static::dateColumn('date', 'Date'),
            self::organizationContact(),
            self::description(),
            Tables\Columns\TextColumn::make('paymentable_type')
                ->formatStateUsing(function ($record) {
                    return getLabelFromModelClass($record->paymentable_type);
                })
                ->tooltip(function ($record): string {
                    if ($record->paymentable) {
                        $displayField = $record->paymentable->getDisplayField();

                        return 'Nr.: '.$record->paymentable->number.' - Data: '.Carbon::parse($record->paymentable->date)->toFormattedDateString().' - '.$record->paymentable->$displayField;
                    } else {
                        return '';
                    }
                })
                ->url(function ($record) {
                    return $record->paymentable
                        ? $record->paymentable->getResourceClass()::getUrl('edit', ['record' => $record->paymentable])
                        : '';
                })
                ->wrap()
                ->color('success')
                ->label(__('payment-receipt.Origin')),
            Tables\Columns\TextColumn::make('paymentMethod.name')
                ->searchable()
                ->wrap()
                ->label(__('payment-receipt.Payment Method')),
            Tables\Columns\TextColumn::make('debit')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->color(function ($record): string {
                    if ($record->debit < 0) {
                        $color = 'success';
                    } elseif ($record->debit > 0) {
                        $color = 'danger';
                    } else {
                        $color = '';
                    }

                    return $color;
                })
                ->label(__('payment-receipt.Debit'))
                ->summarize(Summarizer::make()
                    ->label('Total Debits')
                    ->using(fn (\Illuminate\Database\Query\Builder $query) => view('filament.tables.columns.payments-receipts-totals-debits', [
                        'totalPayments' => $query->sum('debit'),
                        'currencyPrefix' => UserService::getCurrencyPrefix(),
                    ])
                    )),
            Tables\Columns\TextColumn::make('credit')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->color(function ($record): string {
                    if ($record->credit > 0) {
                        $color = 'success';
                    } elseif ($record->credit < 0) {
                        $color = 'danger';
                    } else {
                        $color = '';
                    }

                    return $color;
                })
                ->label(__('payment-receipt.Credit'))
                ->summarize(Summarizer::make()
                    ->label('Total Credits')
                    ->using(fn (\Illuminate\Database\Query\Builder $query) => view('filament.tables.columns.payments-receipts-balance', [
                        'totalCredits' => $query->sum('credit'),
                        'totalDebits' => $query->sum('debit'),
                        'currencyPrefix' => UserService::getCurrencyPrefix(),
                    ])
                    )),
            static::team(),
            static::user(),
            static::members(),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::teamFilter(),
            static::userFilter(),
            static::organizationFilter(),
            static::contactFilter(),
            Tables\Filters\Filter::make('date')
                ->form([
                    DatePicker::make('date_from')->label(__('payment-receipt.Date From')),
                    DatePicker::make('date_until')->label(__('payment-receipt.Date Until')),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['date_from'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                        )
                        ->when(
                            $data['date_until'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                        );
                })
                ->indicateUsing(function ($state) {
                    $indicator = $state['date_from'] ? __('payment-receipt.Date From').': '.Carbon::parse($state['date_from'])->toFormattedDateString().', ' : '';
                    $indicator .= $state['date_until'] ? __('payment-receipt.Date Until').': '.Carbon::parse($state['date_until'])->toFormattedDateString() : '';

                    return $indicator;
                }),
            Tables\Filters\SelectFilter::make('origin')
                ->label(__('payment-receipt.Origin'))
                ->options([
                    'no_origin' => __('payment-receipt.No Origin'),
                    SaleContract::class => getLabelFromModelClass(SaleContract::class),
                    ProjectProject::class => getLabelFromModelClass(ProjectProject::class),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'] == '',
                            fn (Builder $query, $origin): Builder => $query
                        )
                        ->when(
                            $data['value'],
                            fn (Builder $query, $origin): Builder => $origin == 'no_origin'
                            ? $query->whereNull('paymentable_id')
                            : $query->where('paymentable_type', '=', $origin)
                        );
                })
                ->indicateUsing(function ($state) {
                    if ($state['value'] == 'no_origin') {
                        $indicator = __('payment-receipt.Origin').': '.__('payment-receipt.No Origin');
                    } elseif ($state['value'] && $state['value'] !== 'no_origin') {
                        $indicator = __('payment-receipt.Origin').': '.getLabelFromModelClass($state['value']);
                    } else {
                        $indicator = '';
                    }

                    return $indicator;
                }),
        ];
    }

    public static function trashedFilter(): TrashedFilter
    {
        return TrashedFilter::make()
            ->visible(false);
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction(),
            self::editAction(),
            Tables\Actions\ActionGroup::make([
                AddLinkedTransactionAction::make('add_linked_transaction'),
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
                self::bulkAttachMember(),
                self::deleteBulkAction(),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MemberRelationManager::class,
            RelationManagers\AttachmentRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.accountings');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.payment_receipts');
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
        ];
    }
}
