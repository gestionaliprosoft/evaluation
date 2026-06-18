<x-filament-breezy::grid-section md=2 title="{{ __('profile.Purchased Ticket Packages') }}" description="{{ __('Manage Your Purchased Ticket Packages') }}">
    <x-filament::card>
        <x-filament::section>
            <x-slot name="heading">
                {{ __('profile.Purchased Ticket Packages') }}
            </x-slot>

            {{ $this->table }}
        </x-filament::section>
    </x-filament::card>

</x-filament-breezy::grid-section>

