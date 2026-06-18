<div>
    <form wire:submit.prevent="submit" class="row g-3" enctype="multipart/form-data">
        @csrf

        <div class="mt-16 grid grid-cols-1 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-3 gap-2 p-2">
            <x-filament::section>
                <x-slot name="heading">
                    {{ __('automation.Module') }}
                </x-slot>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model="workFlows" wire:change="loadFirstRole">
                        @foreach ($workFlowsItems as $workFlowsItem)
                            <option value="{{$workFlowsItem['value']}}">{{$workFlowsItem['label']}}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </x-filament::section>

            @if(!empty($workFlows))
                <x-filament::section>
                    <x-slot name="heading">
                        {{ (__('resources.TeamResource')) }}
                    </x-slot>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model="team" wire:change="resetSelectRole">
                            @foreach ($teams as $team)
                                <option value="{{$team['value']}}">{{$team['label']}}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">
                        {{ (__('automation.Role')) }}
                    </x-slot>

                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model="role" wire:change="loadFirstRole">
                            @foreach ($roles as $role)
                                <option value="{{$role['value']}}">{{$role['label']}}</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </x-filament::section>
            @endif
        </div>

        @if($elementArray)
            <div class="mt-16 grid grid-cols-1 sm:grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-1 p-1">
                <x-filament::section>
                    <x-slot name="heading">
                        {{ __('automation.Statuses') }}
                    </x-slot>

                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                        <thead class="divide-y divide-gray-200 dark:divide-white/5">
                            <tr class="bg-gray-50 dark:bg-white/5">
                                <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-left">
                                    <label>
                                        <x-filament::input.checkbox wire:model="checkAll" wire:click="checkAllStatuses" />
                                        <span class="ml-2">
                                            {{ __('Select All') }}
                                        </span>
                                    </label>
                                </th>
                                @foreach ($elementArray as $ea)
                                    <th class="fi-ta-header-cell px-3 py-3.5 sm:first-of-type:ps-6 sm:last-of-type:pe-6 text-center"> {{ $ea['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                            @foreach ($elementArray as $eaX)
                            <tr class="fi-ta-row [@media(hover:hover)]:transition [@media(hover:hover)]:duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3 fi-ta-selection-cell w-1">
                                    {{ $eaX['label'] }}
                                </td>
                                @foreach ($elementArray as $eaY )
                                <td class="fi-ta-cell p-0 first-of-type:ps-1 last-of-type:pe-1 sm:first-of-type:ps-3 sm:last-of-type:pe-3 fi-ta-selection-cell w-1 text-center">
                                    <div class="form-check">
                                        <label>
                                            <x-filament::input.checkbox
                                                wire:model="checkedArray.{{ $eaX['id'] }}.{{ $eaY['id'] }}"
                                                wire:key="checkedArray.{{ $eaX['id'] }}.{{ $eaY['id'] }}"
                                            />
                                        </label>
                                    </div>
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @can('update_workflow')
                        <x-filament::button type="submit" class="mt-3">
                            {{ __('automation.Save') }}
                        </x-filament::button>
                    @endcan
                </x-filament::section>
            </div>
        @endif

    </form>
</div>
