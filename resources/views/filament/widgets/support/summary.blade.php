<x-filament::section>
    <x-slot name="heading">
        {{ __('support.Summary' )}}
    </x-slot>

    <x-filament::fieldset class="mb-4">
        <x-slot name="label">
            <b>{{ __('support.Summary Ticket Tipology Short Description') }}</b>
        </x-slot>

        <x-filament::badge>
            {{ __('Category').': '.$category }}
        </x-filament::badge>
        <x-filament::badge>
            {{ __('support.Subject').': '.$subject }}
        </x-filament::badge>
        <x-filament::badge>
            {{ __('ticket.Intervention Tipology').': '.$intervention }}
        </x-filament::badge>
    </x-filament::fieldset>


    <x-filament::fieldset class="mb-4">
        <x-slot name="label">
            <b>{{ __('support.Ticket Message') }}</b>
        </x-slot>

        {{ $message }}
    </x-filament::fieldset>


    <x-filament::fieldset>
        <x-slot name="label">
            <b>{{ __('support.Request Integrity Check') }}</b>
        </x-slot>

        {{ $summaryMessage }}
    </x-filament::fieldset>
</x-filament::section>
