<?php

namespace App\Traits\Filters;

use App\Libs;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vendor;
use App\Services\TeamService;
use Filament\Tables;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;

trait HasFilters
{
    public static function teamFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('team_id')
            ->label('Team')
            ->options(fn (TeamService $teamService) => $teamService->getAllowedTeams())
            ->searchable()
            ->preload()
            ->visible(fn () => auth()->user()->hasRole(['super_admin']));
    }

    public static function tenantFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('tenant.name')
            ->relationship('tenant', 'name')
            ->searchable()
            ->preload()
            ->visible(fn () => auth()->user()->hasRole(['super_admin']));
    }

    public static function userFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('userId')
            ->label(__('User'))
            ->options(Libs\UserService::getAllowedUsers())
            ->searchable()
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['value'],
                        fn (Builder $query, $userId): Builder => $query
                            ->where('user_id', $userId)
                    );
            })
            ->indicateUsing(function ($data): ?string {
                $user = User::find($data['value']);

                return $data['value'] ? 'User: '.$user->name.' '.$user->surname : null;
            })
            ->visible(fn () => auth()->user()->hasRole(['super_admin']));
    }

    public static function organizationFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('organization')
            ->relationship('organization', 'name')
            ->label(__('resources.OrganizationResource'))
            ->options(Organization::getOptionsForSelect())
            ->searchable()
            ->preload();
    }

    public static function vendorFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('vendor')
            ->relationship('vendor', 'name')
            ->label(__('resources.VendorResource'))
            ->options(Vendor::getOptionsForSelect())
            ->searchable()
            ->preload();
    }

    public static function contactFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('contact')
            ->relationship('contact', 'full_name')
            ->label(__('resources.ContactResource'))
            ->options(Contact::getOptionsForSelect())
            ->searchable()
            ->preload();
    }

    public static function deadlineFilter()
    {
        return TernaryFilter::make('deadline')
            ->label(__('Deadline'))
            ->placeholder(__('All'))
            ->trueLabel(__('Currently Valid'))
            ->falseLabel(__('Expired'))
            ->queries(
                true: fn (Builder $query) => $query->where('valid_until', '>=', now()),
                false: fn (Builder $query) => $query->where('valid_until', '<', now()),
                blank: fn (Builder $query) => $query,
            );
    }

    public static function trashedFilter(): TrashedFilter
    {
        return TrashedFilter::make()
            ->visible(auth()->user()->hasRole('super_admin'));
    }

    public static function enabledDisabledFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('enabled')
            ->label(__('Enabled'))
            ->options([
                true => 'Enabled',
                false => 'Disabled',
            ]);
    }
}
