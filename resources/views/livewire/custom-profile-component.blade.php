<x-filament-breezy::grid-section md=2 title="{{ __('profile.Your Custom settings') }}" description="{{ __('profile.Manage Your Custom settings') }}">
    <x-filament::card>
        <form wire:submit.prevent="updateProfileSettings" class="space-y-6">

            {{ $this->form }}

            <div class="text-right">
                <x-filament::button type="submit" form="submit" class="align-right">
                    {{ __('Update') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>    
</x-filament-breezy::grid-section>

