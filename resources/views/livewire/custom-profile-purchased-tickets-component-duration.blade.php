<x-filament::badge color="info">
    {{ __('ticket.Purchased on').': '.$purchasedOn }}
</x-filament::badge>

<x-filament::badge color="warning">
    {{__('ticket.Expire on').': '.$expire }}
</x-filament::badge>

<x-filament::badge color="{{ $remain < 0 ? 'danger' : 'success' }}">
    {{ $remain < 0 ? __('ticket.Expired') : __('ticket.Remain') }}: {{ $remain }} {{ __('ticket.days') }}
</x-filament::badge>

