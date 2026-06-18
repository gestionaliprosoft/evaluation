<?php

namespace App\Filament\Clusters\Tickets\Resources;

use App\Enums\TicketPriorityEnum;
use App\Filament\Clusters\Tickets;
use App\Filament\Clusters\Tickets\Resources\TicketResource\Pages;
use App\Filament\Clusters\Tickets\Resources\TicketResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\Ticket\AttachOrganizationAction;
use App\Filament\Tables\Actions\Ticket\DetachOrganizationAction;
use App\Libs\FormService;
use App\Libs\TicketService;
use App\Libs\WorkflowService;
use App\Models\Contact;
use App\Models\Domain\DomainHosting;
use App\Models\Opportunity;
use App\Models\Organization;
use App\Models\Project\ProjectProject;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use App\Models\Ticket\TicketIntervention;
use App\Models\Ticket\TicketPackage;
use App\Models\Ticket\TicketStatus;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\BaseSettings;
use App\Traits\Commentables\HasCommentableActions;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Summary of TicketResource
 */
class TicketResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;
    use HasCommentableActions;

    protected static ?string $model = Ticket::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Tickets::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => self::getCssStatus($record))
            ->defaultSort('ticket_date', 'desc')
            ->recordUrl(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'view' => Pages\ViewTicket::route('/{record}'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
            'activities' => Pages\ListTicketActivities::route('/{record}/activities'),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\DatePicker::make('ticket_date')
                                ->label(__('Date'))
                                ->required()
                                ->default(now()),
                            Forms\Components\Select::make('ticket_category_id')
                                ->default(TicketService::getDefaultCategory())
                                ->label(__('Category'))
                                ->afterStateUpdated(fn (Set $set) => $set('ticket_intervention_id', ''))
                                ->options(fn (Get $get) => TicketCategory::getOptionsForSelect($get('ticket_category_id')))
                                ->searchable(['name'])
                                ->preload()
                                ->live(),
                            Forms\Components\Select::make('ticket_intervention_id')
                                ->label(__('ticket.Intervention Tipology'))
                                ->live()
                                ->options(function (Get $get) {
                                    if ($get('ticket_category_id')) {
                                        return TicketService::getAllowedInterventions($get('ticket_category_id'));
                                    }
                                })
                                ->afterStateUpdated(function (Get $get, Set $set, $operation, TicketService $ticketService) {
                                    if ($get('ticket_intervention_id') && $operation == 'create') {
                                        $ticketIntervention = TicketIntervention::find($get('ticket_intervention_id'));

                                        $ticketDefaultPackageId = $ticketService::getDefaultPackage();

                                        if ($ticketDefaultPackageId) {
                                            $ticketDefaultPackage = TicketPackage::find($ticketDefaultPackageId);
                                            $ticketDefaultPackage->ticketInterventions()->attach($ticketIntervention);

                                            $set('title', $ticketIntervention->name);
                                            $set('message', $ticketIntervention->description);
                                        }
                                    }
                                })
                                ->searchable(['name']),
                        ])->columns(3),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\TextInput::make('title')
                                ->label(__('Title'))
                                ->required()
                                ->maxLength(255),
                        ])->columns(1),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\RichEditor::make('message')
                                ->label(__('Description')),
                        ])->columns(1),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\Select::make('priority')
                                ->label(__('Priority'))
                                ->default(TicketPriorityEnum::NORMAL)
                                ->required()
                                ->options(TicketPriorityEnum::class),
                            Forms\Components\DatePicker::make('close_date')
                                ->label(__('Close Date'))
                                ->disabled(function (Get $get) {
                                    return TicketStatus::where('id', $get('ticket_status_id'))->value('archived');
                                }),
                            Forms\Components\Select::make('ticket_status_id')
                                ->label(__('Status'))
                                ->options(function ($record): mixed {
                                    return WorkflowService::getWorkflowOptions(TicketStatus::class, $record?->ticket_status_id);
                                })
                                ->default(function ($record) {
                                    return WorkflowService::getWorkFlowDefaultPermittedOption(TicketStatus::class, $record);
                                })
                                ->searchable(['status'])->live(onBlur: true),
                            FormService::attachmentImageFileUploadFormSection(
                                'Ticket',
                                __('Upload Documents'),
                            ),
                        ])->columns(3),
                    ]),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedFormSection(),
                    FormService::belongsToFormSection([
                        User::class => getLabelFromModelClass(User::class),
                        Contact::class => getLabelFromModelClass(Contact::class),
                        Opportunity::class => getLabelFromModelClass(Opportunity::class),
                        Organization::class => getLabelFromModelClass(Organization::class),
                        Vendor::class => getLabelFromModelClass(Vendor::class),
                        DomainHosting::class => getLabelFromModelClass(DomainHosting::class),
                        ProjectProject::class => getLabelFromModelClass(ProjectProject::class),
                    ]),
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
            static::dateColumn('ticket_date', 'Date')
                ->description(fn ($record) => __('Updated At').': '.Carbon::parse($record->updated_at)->format(auth()->user()->date_format)),
            Tables\Columns\TextColumn::make('title')
                ->searchable()
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.ticket-title', [
                        'documents' => $record->attachments->count(),
                        'activities' => $record->times->count(),
                        'title' => $record->title,
                        'message' => $record->message,
                    ]);
                })
                ->wrap()
                ->label(__('Title'))
                ->lineClamp(6),
            Tables\Columns\TextColumn::make('ticketCategory.name')
                ->searchable()
                ->wrap()
                ->label(__('Category')),
            Tables\Columns\SelectColumn::make('priority')
                ->options(TicketPriorityEnum::class)
                ->searchable()
                ->label(__('Priority'))
                ->disabled(fn (Ticket $record) => ! auth()->user()->can('update', $record)),
            Tables\Columns\SelectColumn::make('ticket_status_id')
                ->options(function ($record) {
                    if (auth()->user()->hasRole('super_admin')) {
                        return WorkflowService::getAllowedNoTeamedStatuses(TicketStatus::class, $record);
                    } else {
                        return WorkflowService::getPermittedWorkflows(
                            TicketStatus::class,
                            ['id', 'status', 'is_default', 'is_editable', 'to_process', 'is_processing', 'is_final_step', 'archived'],
                            'sorting',
                            'asc',
                            $record->ticket_status_id
                        );
                    }
                })
                ->label(__('Status'))
                ->disabled(fn (Ticket $record) => ! auth()->user()->can('update', $record->status)),
            static::dateColumn('close_date', 'Close Date'),
            Tables\Columns\TextColumn::make('ticketable')
                ->formatStateUsing(function (Ticket $record) {
                    return match ($record->ticketable_type) {
                        User::class => $record->ticketable->name.' '.$record->ticketable->surname ,
                        Contact::class => $record->ticketable->full_name,
                        Opportunity::class => $record->ticketable->name,
                        Organization::class => $record->ticketable->name,
                        Vendor::class => $record->ticketable->name,
                        DomainHosting::class => $record->ticketable->name,
                        ProjectProject::class => $record->ticketable->name,
                    };
                })
                ->description(function ($record): array|string|null {
                    return getLabelFromModelClass($record->ticketable_type);
                })
                ->wrap()
                ->label(__('Belongs To'))
                ->url(function ($record) {
                    $class = null;

                    if ($record->ticketable) {
                        $class = $record->ticketable->getResourceClass();
                    }

                    return $class ? $class::getUrl('edit', ['record' => $record->ticketable]) : '';
                }),
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
            Tables\Filters\SelectFilter::make('status')
                ->label(__('Status'))
                ->options(fn () => WorkflowService::getAllowedNoTeamedStatuses(TicketStatus::class))
                ->searchable()
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query, $date): Builder => $query->where('ticket_status_id', $data['value']),
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
            self::replicaAction(),
            static::commentableAction(),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                AttachOrganizationAction::make('attach_organization'),
                DetachOrganizationAction::make('detach_organization'),
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
            RelationManagers\TimeRelationManager::class,
            RelationManagers\MemberRelationManager::class,
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
            'manage_member',
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.tickets');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.tickets');
    }

    public static function replicaAction()
    {
        return Tables\Actions\ReplicateAction::make()
            ->label('')
            ->tooltip(__('Replicate'))
            ->beforeReplicaSaved(function ($replica): void {
                $replica->uuid = Str::uuid();
                $replica->title = '[NEW] '.$replica->title;
            })
            ->successRedirectUrl(fn (Model $replica): string => TicketResource::getUrl('edit', [
                'record' => $replica->getKey(),
            ]));
    }

    protected static function showTimestamps()
    {
        return false;
    }

    protected static function getCssStatus($record): ?string
    {
        if ($record->status?->archived) {
            $cssClass = 'warning-row';
        } elseif ($record->status?->is_final_step) {
            $cssClass = 'success-row';
        } else {
            $cssClass = null;
        }

        return $cssClass;
    }
}
