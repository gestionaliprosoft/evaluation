<?php

namespace App\Filament\Clusters\Projects\Resources;

use App\Filament\Clusters\Projects;
use App\Filament\Clusters\Projects\Resources\ProjectProjectResource\Pages;
use App\Filament\Clusters\Projects\Resources\ProjectProjectResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Filament\Tables\Actions\ProjectProject\AttachContractAction;
use App\Filament\Tables\Actions\ProjectProject\AttachOpportunityAction;
use App\Filament\Tables\Actions\ProjectProject\AttachTransactionAction;
use App\Filament\Tables\Actions\ProjectProject\DetachContractAction;
use App\Filament\Tables\Actions\ProjectProject\DetachOpportunityAction;
use App\Libs\FormService;
use App\Libs\PicklistService;
use App\Libs\TicketService;
use App\Libs\UserService;
use App\Libs\WorkflowService;
use App\Models\Opportunity;
use App\Models\Project\ProjectProject;
use App\Models\Project\ProjectStatus;
use App\Models\Sale\SaleContract;
use App\Services\ModuleSettingService;
use App\Traits\BaseSettings;
use App\Traits\Commentables\HasCommentableActions;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProjectProjectResource extends Resource implements HasShieldPermissions
{
    use BaseSettings;
    use HasCommentableActions;

    protected const START_DATE_LABEL = 'project-project.Start Date';

    protected const END_DATE_LABEL = 'project-project.End Date';

    protected const EFFECTIVE_END_DATE_LABEL = 'project-project.Effective End Date';

    protected static ?string $model = ProjectProject::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Projects::class;

    public static function getGloballySearchableAttributes(): array
    {
        return ['organization.name', 'name'];
    }

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => WorkflowService::getCssStatus($record))
            ->recordUrl(null)
            ->defaultSort('date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjectProjects::route('/'),
            'create' => Pages\CreateProjectProject::route('/create'),
            'view' => Pages\ViewProjectProject::route('/{record}'),
            'edit' => Pages\EditProjectProject::route('/{record}/edit'),
            'activities' => Pages\ListProjectProjectActivities::route('/{record}/activities'),
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
                            Forms\Components\TextInput::make('number')
                                ->visibleOn('view'),
                            Forms\Components\TextInput::make('uuid')
                                ->label(__('Uuid'))
                                ->visibleOn('view'),
                        ])->columns(2),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\DatePicker::make('date')
                                ->label(__('Date'))
                                ->default(now())
                                ->required(),
                            Forms\Components\TextInput::make('name')
                                ->label(__('Name'))
                                ->required(),
                        ])->columns(2),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\Textarea::make('description')
                                ->label(__('Description'))
                                ->rows(8),
                        ])->columns(1),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\DatePicker::make('start_date')
                                ->label(__(self::START_DATE_LABEL))
                                ->default(now())
                                ->required(),
                            Forms\Components\DatePicker::make('end_date')
                                ->label(__(self::END_DATE_LABEL)),
                            Forms\Components\DatePicker::make('real_end_date')
                                ->label(__(self::EFFECTIVE_END_DATE_LABEL)),
                        ])->columns(3),
                        Forms\Components\Grid::make('')->schema([
                            FormService::selectOrganization()
                                ->disabled(fn () => request()->has('organizationId')),
                        ])->columns(2),
                        Forms\Components\Grid::make('')->schema([
                            Forms\Components\Select::make('type')
                                ->label(__('Type'))
                                ->options(PicklistService::getPicklistsByFieldName('type', 'projectProject'))
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('project_status_id')
                                ->label(__('project-project.Status'))
                                ->options(function ($record): array|Collection {
                                    return WorkflowService::getWorkflowOptions(ProjectStatus::class, $record?->project_status_id);
                                })
                                ->default(function ($record) {
                                    return WorkflowService::getWorkFlowDefaultPermittedOption(ProjectStatus::class, $record);
                                })
                                ->searchable(['status']),
                            Forms\Components\Select::make('progress')
                                ->label(__('project-project.Progress'))
                                ->prefix('%')
                                ->options(PicklistService::getPicklistsByFieldName('progress', 'projectProject'))
                                ->searchable()
                                ->required()
                                ->default(0)
                                ->preload(),
                        ])->columns(3),
                        FormService::attachmentImageFileUploadFormSection(
                            'ProjectProject',
                            __('Upload Documents'),
                        ),
                    ]),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\TextInput::make('project_value')
                            ->label(__('project-project.Project Value'))
                            ->prefix(UserService::getCurrencyPrefix())
                            ->currencyMask(thousandSeparator: '.', decimalSeparator: ',', precision: 2),
                    ])->columns(1),
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
            Tables\Columns\TextColumn::make('number')
                ->label(__('Nr.'))
                ->sortable()
                ->searchable(),
            static::dateColumn('date', 'Date'),
            Tables\Columns\TextColumn::make('name')
                ->formatStateUsing(function ($record) {
                    return view('filament.tables.columns.project-name', [
                        'documents' => $record->attachments->count(),
                        'name' => $record->name,
                        'tickets' => TicketService::getAllTicketsCount($record),
                        'payments' => $record->paymentReceipts?->count(),
                    ]);
                })
                ->label(__('Name'))
                ->wrap()
                ->searchable(),
            static::dateColumn('start_date', self::START_DATE_LABEL),
            static::dateColumn('end_date', self::END_DATE_LABEL),
            static::dateColumn('real_end_date', self::EFFECTIVE_END_DATE_LABEL),
            Tables\Columns\SelectColumn::make('project_status_id')
                ->options(function ($record) {
                    if (auth()->user()->hasRole('super_admin')) {
                        return WorkflowService::getAllowedNoTeamedStatuses(ProjectStatus::class, $record);
                    } else {
                        return WorkflowService::getPermittedWorkflows(
                            'App\\Models\\Project\\ProjectStatus',
                            ['id', 'status', 'is_default', 'is_editable', 'to_process', 'is_processing', 'is_final_step', 'archived'],
                            'sorting',
                            'asc',
                            $record->project_status_id
                        );
                    }
                })
                ->searchable()
                ->label(__('project-project.Status'))
                ->disabled(fn (ProjectProject $record) => ! auth()->user()->can('update', $record) && ! auth()->user()->hasRole(['super_admin'])),
            Tables\Columns\TextColumn::make('progress')
                ->description(fn ($record) => __('Type').': '.$record->type)
                ->suffix('%')
                ->alignCenter()
                ->label(__('project-project.Progress')),
            Tables\Columns\TextColumn::make('project_value')
                ->prefix(UserService::getCurrencyPrefix())
                ->alignRight()
                ->sortable()
                ->label(__('project-project.Project Value'))
                ->summarize([
                    Sum::make()
                        ->label(__('project-project.Total Projects'))
                        ->money(auth()->user()->currency),
                ]),
            Tables\Columns\TextColumn::make('belongs_to')
                ->state(function ($record) {
                    if ($record->contract) {
                        return getLabelFromModelClass(SaleContract::class);
                    } elseif ($record->opportunity) {
                        return getLabelFromModelClass(Opportunity::class);
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
                    } elseif ($record->opportunity) {
                        $class = $record->opportunity->getResourceClass();
                        $recordClass = $record->opportunity;
                    }

                    return $class ? $class::getUrl('edit', ['record' => $recordClass]) : '';
                })
                ->wrap()
                ->label(__('Belongs To')),
            self::organizationContact(),
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
            Tables\Filters\Filter::make('Progress')
                ->form([
                    Forms\Components\Select::make('progress')
                        ->label(__('project-project.Progress'))
                        ->options([
                            'to_start' => __('project-project.To Start'),
                            'in_progress' => __('project-project.On Progress'),
                            'completed' => __('project-project.Completed'),
                        ]),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    switch ($data['progress']) {
                        case 'to_start':
                            $query->where('progress', '=', 0);
                            break;
                        case 'in_progress':
                            $query->where('progress', '>', 0)->where('progress', '<', 100);
                            break;
                        case 'completed':
                            $query->where('progress', '=', 100);
                            break;
                        default:
                            // return only query
                            break;
                    }

                    return $query;
                }),
            Tables\Filters\Filter::make('validity')
                ->form([
                    Forms\Components\DatePicker::make('start_date')->label(__(self::START_DATE_LABEL)),
                    Forms\Components\DatePicker::make('real_end_date')->label(__(self::EFFECTIVE_END_DATE_LABEL)),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['start_date'],
                            fn (Builder $query, $validity): Builder => $query->whereDate('start_date', '>=', $validity),
                        )
                        ->when(
                            $data['real_end_date'],
                            fn (Builder $query, $validity): Builder => $query->whereDate('real_end_date', '<=', $validity),
                        );
                })
                ->indicateUsing(function ($state) {
                    $indicator = $state['start_date'] ? __(self::START_DATE_LABEL).': '.Carbon::parse($state['start_date'])->toFormattedDateString().', ' : '';
                    $indicator .= $state['real_end_date'] ? __(self::EFFECTIVE_END_DATE_LABEL).': '.Carbon::parse($state['real_end_date'])->toFormattedDateString() : '';

                    return $indicator;
                }),
            Tables\Filters\SelectFilter::make('status')
                ->label(__('project-project.Status'))
                ->options(fn () => WorkflowService::getAllowedNoTeamedStatuses(ProjectStatus::class))
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['value'],
                            fn (Builder $query, $date): Builder => $query->where('project_status_id', $data['value']),
                        );
                })
                ->preload(),
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
                ->beforeReplicaSaved(function ($replica, ModuleSettingService $moduleSettingService): void {
                    $replica['uuid'] = Str::uuid();
                    $replica['number_seq'] = ProjectProject::where('team_id', $replica['team_id'])->orderBy('id', 'desc')->value('number_seq') + 1;
                    $replica['number'] = $moduleSettingService->getModuleSettings('ProjectProjects', 'number').$replica['number_seq'];
                })
                ->successRedirectUrl(fn (Model $replica): string => ProjectProjectResource::getUrl('edit', [
                    'record' => $replica->getKey(),
                ])),
            static::commentableAction(),
            Tables\Actions\RestoreAction::make()
                ->after(fn () => redirect(self::getUrl('index'))),
            Tables\Actions\ActionGroup::make([
                AttachContractAction::make('Attach Contract'),
                DetachContractAction::make('Detach Contract'),
                AttachOpportunityAction::make('Attach Opportunity'),
                DetachOpportunityAction::make('Detach Opportunity'),
                AttachTransactionAction::make('Attach Transaction'),

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
            RelationManagers\TicketsRelationManager::class,
            RelationManagers\ContactRelationManager::class,
            RelationManagers\SaleContractRelationManager::class,
            RelationManagers\OpportunityRelationManager::class,
            RelationManagers\PaymentReceiptRelationManager::class,
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
        return __('navigations.group.projects');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.projects');
    }
}
