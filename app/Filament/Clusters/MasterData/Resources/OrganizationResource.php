<?php

namespace App\Filament\Clusters\MasterData\Resources;

use App\Filament\Clusters\Accountings\Resources\PaymentMethodResource;
use App\Filament\Clusters\MasterData;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\Pages;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\AttachmentRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\ContactsRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\ContractsRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\DomainsRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\MemberRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\ProjectsRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\QuotesRelationManager;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\TicketsRelationManager;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs\FormService;
use App\Libs\PicklistService;
use App\Libs\TicketService;
use App\Models\Accounting\PaymentMethod;
use App\Models\Organization;
use App\Services\AddressService;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Tables\Actions\EmailMessage\SendEmailMessageAction;
use App\Filament\Clusters\MasterData\Resources\OrganizationResource\RelationManagers\EmailMessageRelationManager;

class OrganizationResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected static ?string $model = Organization::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = MasterData::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'primary_email'];
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
            'index' => Pages\ListOrganizations::route('/'),
            'create' => Pages\CreateOrganization::route('/create'),
            'view' => Pages\ViewOrganization::route('/{record}'),
            'edit' => Pages\EditOrganization::route('/{record}/edit'),
            'activities' => Pages\ListOrganizationActivities::route('/{record}/activities'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Forms\Components\Tabs::make('Tabs')
                        ->tabs([
                            Forms\Components\Tabs\Tab::make(__('Basic Informations'))->schema([
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\TextInput::make('name')
                                        ->label(__('Name'))
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('primary_email')
                                        ->label(__('Email'))
                                        ->email()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('secondary_email')
                                        ->email()
                                        ->label(__('organization.Secondary Email'))
                                        ->maxLength(255),
                                ])->columns(3),
                                Forms\Components\Grid::make('')->schema([
                                    FormService::phoneField('primary_phone', 'organization.Primary Phone'),
                                    FormService::phoneField('secondary_phone', 'organization.Secondary Phone'),
                                    FormService::phoneField('mobile_phone', 'organization.Mobile Phone'),
                                ])->columns(3),
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\TextInput::make('legal_representative')
                                        ->label(__('organization.Legal Representative'))
                                        ->maxLength(60),
                                    Forms\Components\TextInput::make('vat')
                                        ->label(__('organization.Vat'))
                                        ->maxLength(30),
                                    Forms\Components\TextInput::make('tax_id_code')
                                        ->label(__('Tax ID Code'))
                                        ->maxLength(30),
                                ])->columns(3),
                            ]),
                            Forms\Components\Tabs\Tab::make(__('Addresses'))->schema([
                                FormService::addressesRepeater(),
                            ]),
                            Forms\Components\Tabs\Tab::make(__('organization.Others Informations'))->schema([
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\Select::make('industry')
                                        ->label(__('organization.Industry'))
                                        ->options(PicklistService::getPicklistsByFieldName('industry', 'organization'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('rating')
                                        ->label(__('Rating'))
                                        ->options(PicklistService::getPicklistsByFieldName('rating', 'organization'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('type')
                                        ->label(__('Type'))
                                        ->options(PicklistService::getPicklistsByFieldName('type', 'organization'))
                                        ->searchable()
                                        ->preload(),
                                ])->columns(3),
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\TextInput::make('employees')
                                        ->label(__('organization.Nr. of Employees'))
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('website')
                                        ->label(__('Website'))
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('Description')),
                                ])->columns(3),
                            ]),
                            Forms\Components\Tabs\Tab::make(__('organization.Details'))->schema([
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\Select::make('payment_method_id')
                                        ->formatStateUsing(function ($record) {
                                            $details = $record?->details->first() ?? null;

                                            return $details?->payment_method_id;
                                        })
                                        ->label(__('organization.Preferred Payment Method'))
                                        ->options(fn (Get $get) => PaymentMethod::getOptionsForSelect($get('payment_method_id')))
                                        ->searchable(['name'])
                                        ->createOptionUsing(fn (array $data): int => PaymentMethod::create($data)->getKey())
                                        ->createOptionForm(PaymentMethodResource::getFormsComponents())
                                        ->createOptionAction(
                                            fn (Action $action) => $action->modalWidth('7xl'),
                                        )
                                        ->preload(),
                                ])->columns(3),
                            ]),
                            Forms\Components\Tabs\Tab::make(__('Documents'))->schema([
                                FormService::attachmentImageFileUploadFormSection(
                                    'Organization',                               // string nome del model
                                    __('organization.Upload Documents'),     // string label
                                ),
                            ]),
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

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('name')
                ->formatStateUsing(function (Organization $record) {
                    return view('filament.tables.columns.organization-name', [
                        'documents' => $record->attachments->count(),
                        'name' => $record->name,
                        'domains' => $record->domains->count(),
                        'quotes' => $record->quotes->count(),
                        'contracts' => $record->contracts->count(),
                        'projects' => $record->projects->count(),
                        'tickets' => TicketService::getAllTicketsCount($record),
                        'description' => $record->description,
                        'contacts' => $record->contacts->count(),
                    ]);
                })
                ->tooltip(fn (Organization $record) => $record->name)
                ->searchable()
                ->sortable()
                ->wrap()
                ->label(__('Name')),
            Tables\Columns\TextColumn::make('legal_representative')
                ->searchable()
                ->label(__('organization.Legal Representative')),
            self::phone('mobile_phone', 'organization.Mobile Phone'),
            Tables\Columns\TextColumn::make('primary_email')
                ->searchable()
                ->label(__('Email')),
            static::team(),
            static::user(),
            static::members(),
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
                ->afterReplicaSaved(function ($record, $replica) {
                    // create addresses
                    $addressService = new AddressService;
                    $addressService->cloneAddressesFrom($record, $replica);
                })
                ->successRedirectUrl(fn (Model $replica): string => OrganizationResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                SendEmailMessageAction::make(),
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
            ContactsRelationManager::class,
            TicketsRelationManager::class,
            QuotesRelationManager::class,
            ContractsRelationManager::class,
            ProjectsRelationManager::class,
            DomainsRelationManager::class,
            EmailMessageRelationManager::class,
            MemberRelationManager::class,
            AttachmentRelationManager::class,
            ActivitiesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.master-data');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.organizations');
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
            'send_email',
        ];
    }
}
