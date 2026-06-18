@if(auth()->user()->isMainTenantSuperUser() || auth()->user()->can('widget_Calendar'))
    <x-filament::modal width="6xl">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-m-calendar-days"
                label="Calendar"
            />
        </x-slot>

        <x-slot name="heading">
            Calendar
        </x-slot>

        <x-slot name="description">
            <x-filament::link :href="route('filament.admin.resources.calendars.index')">
                {{ __('Full Calendar Page') }}
            </x-filament::link>
        </x-slot>

        <div>
            @livewire(\App\Filament\Widgets\Calendar::class)
        </div>
    </x-filament::modal>
@endif

@if(auth()->user()->isMaintenantSuperUser() || auth()->user()->can('page_Browser'))
    <x-filament::modal width="7x1">
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-m-folder"
                label="Browser"
            />
        </x-slot>

        <x-slot name="heading">
            <x-filament::link :href="route('filament.admin.pages.browser')">
                {{ __('Full File Browser Page') }}
            </x-filament::link>
        </x-slot>

        <div>
            @livewire(\App\Filament\Pages\Browser::class)
        </div>
    </x-filament::modal>
@endif
