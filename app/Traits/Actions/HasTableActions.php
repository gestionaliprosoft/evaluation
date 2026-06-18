<?php

namespace App\Traits\Actions;

use App\Libs\FormService;
use App\Libs\PaymentService;
use App\Libs\TicketService;
use app\Libs\UserService;
use App\Models\ModuleContact;
use App\Models\ModuleMember;
use App\Services\ProjectService;
use App\Services\TenantService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasTableActions
{
    public static function editAction(): EditAction
    {
        return EditAction::make()
            ->label('')
            ->tooltip(__('Edit'))
            ->modalWidth(MaxWidth::Full);
    }

    public static function viewAction(): ViewAction
    {
        return ViewAction::make()
            ->label('')
            ->tooltip(__('View'))
            ->modalWidth(MaxWidth::Full);
    }

    public static function tickets(): Action
    {
        return Action::make('Tickets')
            ->label('')
            ->tooltip(fn ($record): array => TicketService::getAllTicketsCount($record))
            ->icon(fn ($record): ?string => TicketService::getAllTicketsCountIcon($record))
            ->visible(function (Action $action): bool {
                return $action->getIcon() ? true : false;
            })
            ->url(fn ($record) => self::getUrl('view', ['record' => $record]));
    }

    public static function forceDeleteAction(): ForceDeleteAction
    {
        return ForceDeleteAction::make()
            ->label(__('Force Delete'))
            ->after(fn () => redirect(self::getUrl('index')));
    }

    public static function deleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->after(fn () => redirect(self::getUrl('index')));
    }

    public static function forceDeleteBulkAction(): ForceDeleteBulkAction
    {
        return ForceDeleteBulkAction::make()
            ->after(fn () => redirect(self::getUrl('index')));
    }

    public static function restoreBulkAction(): RestoreBulkAction
    {
        return RestoreBulkAction::make()
            ->after(fn () => redirect(self::getUrl('index')));
    }

    public static function changeRecordOwnership(): BulkAction
    {
        return BulkAction::make('changeOwnerRecord')
            ->label(__('custom-actions.Change Owner Record'))
            ->requiresConfirmation()
            ->form([
                FormService::assignedFormSection(),
            ])
            ->action(function (array $data, Collection $records): void {
                foreach ($records as $record) {
                    $record->user_id = $data['user_id'];
                    $record->save();
                }
            })
            ->after(function () {
                return Notification::make()
                    ->title(__('custom-actions.Ownership Changed'))
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion()
            ->visible(function () {
                return auth()->user()->hasRole(['super_admin']);
            });
    }

    public static function seedTable(bool $singleModel = false): Action
    {
        return Action::make('seedFakeRecords')
            ->visible(fn (): bool => auth()->user()->isMainTenantSuperUser())
            ->label(fn ($record) => 'Seed Fake Records '.$record->name.' '.$record->surname)
            ->icon('heroicon-o-circle-stack')
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('Warning: this operation truncate all interested table before inserting new records!')
            ->action(function ($record, TenantService $tenantService) use ($singleModel) {
                $tenantService->executeSeeder($record, $singleModel);
            });
    }

    public static function completeFormAction($resourceClass): Action
    {
        return Action::make('Complete Form')
            ->visible(function ($record) {
                if ($record instanceof ModuleContact) {
                    return auth()->user()->can('update', $record->contact);
                } else {
                    return auth()->user()->can('update', $record);
                }
            })
            ->label(__(''))
            ->tooltip(__('Complete Form'))
            ->icon(config('module-icon.icon-complete-form'))
            ->url(function ($record) use ($resourceClass) {
                if ($record instanceof ModuleContact) {
                    return $resourceClass::getUrl('edit', ['record' => $record->contact]);
                } else {
                    return $resourceClass::getUrl('edit', ['record' => $record]);
                }
            });
    }

    public static function relationManagersPaymentsAction(): Action
    {
        return Action::make('Payments')
            ->label('')
            ->tooltip(fn ($record): ?string => PaymentService::getAllPaymentsCount($record))
            ->icon(fn ($record): ?string => PaymentService::getAllPaymentsCountIcon($record))
            ->visible(function (Action $action): bool {
                if ($action->getIcon()) {
                    return true;
                }

                return false;
            });
    }

    public static function relationManagerProjectsAction(): Action
    {
        return Action::make('Projects')
            ->label('')
            ->tooltip(fn ($record, ProjectService $projectService): ?string => $projectService->getAllProjectsCount($record))
            ->icon(fn ($record, ProjectService $projectService): ?string => $projectService->getAllProjectsCountIcon($record))
            ->visible(function (Action $action): bool {
                if ($action->getIcon()) {
                    return true;
                }

                return false;
            });
    }

    public static function bulkAttachMember(): BulkAction
    {
        return BulkAction::make('bulkAttachMember')
            ->label(__('custom-actions.Attach Member'))
            ->requiresConfirmation()
            ->visible(function () {
                return auth()->user()->can('manageMember', static::getModel());
            })
            ->form([
                Select::make('user_id')
                    ->label(__('resources.UserResources'))
                    ->options(fn () => UserService::getAllowedUsers())
                    ->required()
                    ->searchable(),
            ])
            ->action(function (array $data, Collection $records): void {
                foreach ($records as $record) {
                    if (! ModuleMember::whereMemberableId($record->getKey())->whereMemberableType($record::class)->whereUserId($data['user_id'])->first()) {
                        $memberable = new ModuleMember;
                        $memberable->memberable_id = $record->getKey();
                        $memberable->memberable_type = $record::class;
                        $memberable->user_id = $data['user_id'];

                        $record->members()->save($memberable);
                    }
                }
            })
            ->after(function () {
                return Notification::make()
                    ->title(__('custom-actions.Member Attached'))
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    public static function downloadTableAction(): Action
    {
        return Action::make('download')
            ->label('Download')
            ->visible(function (RelationManager $livewire): bool {
                return auth()->user()->can('download', $livewire->ownerRecord);
            })
            ->icon('heroicon-m-folder-arrow-down')
            ->tooltip(__('Download'))
            ->url(function (Model $record) {
                $team = 'team-'.$record->team_id;
                $folder = Str::afterLast($record->attachable_type, '\\');
                $filename = $record->filename;

                return '/file/'.$team.'/'.$folder.'/'.$filename.'/download/private';
            });
    }
}
