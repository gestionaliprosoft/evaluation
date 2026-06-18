<div>
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }"
        class="grid auto-cols-fr gap-y-2">
            <x-filament::icon-button
                icon="heroicon-m-magnifying-glass"
                wire:click="dispatchFormEvent('recordFinder::search', '{{ $getStatePath() }}')"
                size="lg"
                label="Search"
            />
    </div>
</x-dynamic-component>
</div>
