@php
    $groupName = __('navigations.group.administration');

    $resources = collect(filament()->getCurrentPanel()->getResources())
        ->filter(fn ($resource) => $resource::getNavigationGroup() === $groupName);

    $pages = collect(filament()->getCurrentPanel()->getPages())
        ->filter(fn ($page) => $page::getNavigationGroup() === $groupName);

    $items = $resources->concat($pages)
        ->filter(fn ($component) => $component::canAccess()) // canAccess copre sia Resource che Page
        ->sortBy(fn ($component) => $component::getNavigationSort());
@endphp

<x-filament::dropdown>
    <x-slot name="trigger">
        <x-filament::icon-button
            icon="heroicon-s-cog-8-tooth"
            size="xl"
            tooltip="{{ __('navigations.group.administration') }}"
        />
    </x-slot>

    <x-filament::dropdown.list>
        @foreach ($items as $item)
            <x-filament::dropdown.list.item
                href="{{ $item::getUrl() }}"
                tag="a"
                class="fi-dropdown-list-item flex w-full items-center gap-2 whitespace-nowrap rounded-md p-2 text-sm transition-colors duration-75 outline-none disabled:pointer-events-none disabled:opacity-70 hover:bg-gray-50 focus-visible:bg-gray-50 dark:hover:bg-white/5 dark:focus-visible:bg-white/5 fi-dropdown-list-item-color-gray fi-color-gray"
                icon="{{ $item::getNavigationIcon() ?? '' }}"
            >
                <span class="fi-dropdown-list-item-label flex-1 truncate text-start text-gray-700 dark:text-gray-200">{{ $item::getNavigationLabel() }}</span>
                @if ($item::getnavigationBadge())
                    <span class="fi-badge ml-auto text-xs bg-gray-100 px-2 rounded-md truncate">{{ $item::getNavigationBadge() }}</span>
                @endif
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
