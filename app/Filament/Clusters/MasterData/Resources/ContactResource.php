<?php

namespace App\Filament\Clusters\MasterData\Resources;

use App\Filament\Clusters\MasterData;
use App\Filament\Clusters\MasterData\Resources\ContactResource\Pages;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\ActivitiesRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\AttachmentRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\ContractsRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\DomainsRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\MemberRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\OrganizationsRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\ProjectsRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\QuotesRelationManager;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\TicketsRelationManager;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\Contact\ConvertToOrganizationAction;
use App\Libs\FormService;
use App\Libs\PicklistService;
use App\Libs\TicketService;
use App\Models\Contact;
use App\Services\AddressService;
use App\Traits\BaseSettings;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Tables\Actions\EmailMessage\SendEmailMessageAction;
use App\Filament\Clusters\MasterData\Resources\ContactResource\RelationManagers\EmailMessageRelationManager;

class ContactResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;

    protected static ?string $model = Contact::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = MasterData::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'primary_email'];
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
            'index' => Pages\ListContacts::route('/'),
            'create' => Pages\CreateContact::route('/create'),
            'view' => Pages\ViewContact::route('/{record}'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
            'activities' => Pages\ListContactActivities::route('/{record}/activities'),
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
                                    Forms\Components\Select::make('title')
                                        ->label(__('Title'))
                                        ->options(PicklistService::getPicklistsByFieldName('title', 'contact'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\TextInput::make('first_name')
                                        ->label(__('contact.First Name'))
                                        ->required()
                                        ->maxLength(150),
                                    Forms\Components\TextInput::make('last_name')
                                        ->label(__('contact.Last Name'))
                                        ->required()
                                        ->maxLength(150),
                                ])->columns(3),
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\DatePicker::make('birth_date')
                                        ->date()
                                        ->label(__('contact.Birth Date')),
                                    Forms\Components\TextInput::make('birth_place')
                                        ->label(__('contact.Birth Place')),
                                    FormService::phoneField('mobile_phone', 'contact.Mobile Phone'),
                                ])->columns(3),
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\TextInput::make('primary_email')
                                        ->label(__('Email'))
                                        ->email()
                                        ->maxLength(255),
                                    Forms\Components\TextInput::make('secondary_email')
                                        ->label(__('contact.Secondary Email'))
                                        ->email()
                                        ->maxLength(255),
                                ])->columns(2),
                                Forms\Components\Grid::make('')->schema([
                                    FormService::phoneField('primary_phone', 'contact.Primary Phone'),
                                    FormService::phoneField('secondary_phone', 'contact.Secondary Phone'),
                                ])->columns(2),
                            ]),
                            Forms\Components\Tabs\Tab::make(__('Addresses'))->schema([
                                FormService::addressesRepeater(),

                            ]),
                            Forms\Components\Tabs\Tab::make(__('contact.Others Informations'))->schema([
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\Select::make('source')
                                        ->label(__('Source'))
                                        ->options(PicklistService::getPicklistsByFieldName('source', 'contact'))
                                        ->searchable()
                                        ->preload(),
                                    Forms\Components\Select::make('department')
                                        ->label(__('Department'))
                                        ->options(PicklistService::getPicklistsByFieldName('department', 'contact'))
                                        ->searchable()
                                        ->preload(),
                                ])->columns(2),
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\TextInput::make('vat')
                                        ->label(__('Vat Code'))
                                        ->maxLength(30),
                                    Forms\Components\TextInput::make('tax_id_code')
                                        ->label(__('Tax ID Code'))
                                        ->maxLength(30),
                                ])->columns(2),
                                Forms\Components\Grid::make('')->schema([
                                    Forms\Components\Textarea::make('description')
                                        ->label(__('Description')),
                                ])->columns(1),
                            ]),
                            Forms\Components\Tabs\Tab::make(__('Documents'))->schema([
                                FormService::attachmentImageFileUploadFormSection(
                                    'Contact',                               // string nome del model
                                    __('contact.Upload Documents'),     // string label
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
            Tables\Columns\TextColumn::make('first_name')
                ->searchable()
                ->label(__('contact.First Name')),
            Tables\Columns\TextColumn::make('last_name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.contact-last-name', [
                        'quotes' => $record->quotes->count(),
                        'contracts' => $record->contracts->count(),
                        'domains' => $record->domains->count(),
                        'tickets' => TicketService::getAllTicketsCount($record),
                        'projects' => $record->projects->count(),
                        'lastName' => $record->last_name,
                        'organizations' => $record->organizations->count(),
                        'documents' => $record->attachments->count(),
                    ]);
                })
                ->searchable()
                ->sortable()
                ->label(__('contact.Last Name')),
            self::phone('mobile_phone', 'contact.Mobile Phone'),
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
            static::userFilter(),
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
                ->excludeAttributes(['full_name'])
                ->beforeReplicaSaved(function ($replica): void {
                    $replica->first_name = '[NEW] '.$replica->first_name;
                })
                ->afterReplicaSaved(function ($record, $replica) {
                    // create addresses
                    $addressService = new AddressService;
                    $addressService->cloneAddressesFrom($record, $replica);
                })
                ->successRedirectUrl(fn (Model $replica): string => ContactResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                ConvertToOrganizationAction::make('convert'),
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
            OrganizationsRelationManager::class,
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
        return __('navigations.label.contacts');
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
