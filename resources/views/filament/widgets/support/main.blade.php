<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('support.Ask for Support') }}
        </x-slot>

        <x-slot name="description">
            {{ __('support.Open a Ticket base on purchased Packages') }}
        </x-slot>

        <form wire:submit.prevent="create">
            {{ $this->form }}
        </form>

    </x-filament::section>
</x-filament-widgets::widget>
