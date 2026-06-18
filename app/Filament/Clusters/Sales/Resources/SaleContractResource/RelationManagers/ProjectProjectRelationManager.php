<?php

namespace App\Filament\Clusters\Sales\Resources\SaleContractResource\RelationManagers;

use App\Abstracts\RelationManagers\AbstractRelationManager;
use App\Filament\Clusters\Projects\Resources\ProjectProjectResource;
use App\Libs\WorkflowService;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProjectProjectRelationManager extends AbstractRelationManager
{
    protected static string $relationship = 'projects';

    protected Form $form;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->projects->count();
    }

    public function form(Form $form): Form
    {
        $formComponents = $form->schema(ProjectProjectResource::getFormsComponents());
        $this->form = $formComponents;

        return $formComponents;
    }

    public function table(Table $table): Table
    {
        $completeForm = Tables\Actions\Action::make('Complete Form')
            ->authorize('update_project::project')
            ->label(__(''))
            ->tooltip(__('Complete Form'))
            ->icon(config('module-icon.icon-complete-form'))
            ->url(fn ($record) => ProjectProjectResource::getUrl('edit', ['record' => $record]));

        return $table
            ->columns(ProjectProjectResource::getColumnsComponents())
            ->recordClasses(fn (Model $record) => WorkflowService::getCssStatus($record))
            ->filters(ProjectProjectResource::getFiltersComponents())->deferFilters()->persistFiltersInSession()->filtersFormColumns(2)
            ->actions(array_merge(ProjectProjectResource::getActionsComponents(), [
                static::completeFormAction(ProjectProjectResource::class),
            ]))
            ->bulkActions(actions: ProjectProjectResource::getBulkActionsComponents())
            ->paginated(config('app.paginations.range'))
            ->defaultPaginationPageOption(config('app.paginations.table'))
            ->searchOnBlur(true)
            ->defaultSort('date', 'desc');
    }
}
