<?php

namespace App\Providers;

use App\Http\Middleware\TenantsConnections;
use App\Models\User;
use App\Observers\GlobalModelObserver;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Facades\FilamentAsset;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Rawilk\FilamentPasswordInput\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen('eloquent.deleted: *', function ($eventName, array $data) {
            $model = $data[0] ?? null;
            if ($model instanceof Model) {
                (new GlobalModelObserver)->deleted($model);
            }
        });

        Event::listen('eloquent.restored: *', function ($eventName, array $data) {
            $model = $data[0] ?? null;
            if ($model instanceof Model) {
                (new GlobalModelObserver)->restored($model);
            }
        });

        Event::listen('eloquent.forceDeleted: *', function ($eventName, array $data) {
            $model = $data[0] ?? null;
            if ($model instanceof Model) {
                (new GlobalModelObserver)->forceDeleted($model);
            }
        });

        Gate::define('download-backup', function (User $user) {
            return $user->isMainTenantSuperUser();
        });

        Gate::define('delete-backup', function (User $user) {
            return $user->isMainTenantSuperUser();
        });

        FilamentAsset::register([
            Js::make('stripe-js', 'https://js.stripe.com/v3/'),
        ]);

        Livewire::setUpdateRoute(function ($handle) {
            return Route::post('/livewire/update', $handle)
                ->middleware(
                    'web',
                    TenantsConnections::class,
                );
        });

        DateTimePicker::configureUsing(fn (DateTimePicker $component) => $component->timezone(config('app.user_timezone')));
        DatePicker::configureUsing(fn (DatePicker $component) => $component->timezone(config('app.user_timezone')));
        TextColumn::configureUsing(fn (TextColumn $column) => $column->timezone(config('app.user_timezone')));

        Notifications::alignment(Alignment::Center);
        Notifications::verticalAlignment(VerticalAlignment::Start);

        Password::configureUsing(function (Password $password) {
            $password
                ->revealable()
                ->regeneratePassword()
                ->newPasswordLength(15)
                ->copyable();
        });
    }
}
