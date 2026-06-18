<?php

namespace App\Livewire;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Jeffgreco13\FilamentBreezy\Livewire\PersonalInfo;

class CustomPersonalInfoComponent extends PersonalInfo
{
    public array $only = [
        'name',
        'surname',
        'email',
    ];

    protected function getNameComponent(): TextInput
    {
        return
            TextInput::make('name')
                ->label(__('Name'))
                ->readOnly(auth()->user()->tenant->id == (int) config('demo.demo_default_tenant_id'))
                ->required()
                ->maxLength(255);
    }

    protected function getSurnameComponent(): TextInput
    {
        return
            TextInput::make('surname')
                ->required()
                ->readOnly(auth()->user()->tenant->id == (int) config('demo.demo_default_tenant_id'))
                ->label(__('Surname'))
                ->maxLength(255);
    }

    protected function getEmailComponent(): TextInput
    {
        return
            TextInput::make('email')
                ->readOnly(auth()->user()->tenant->id == (int) config('demo.demo_default_tenant_id'))
                ->label(__('Email'));
    }

    protected function getProfileFormSchema(): array
    {
        $groupFields = Group::make([
            $this->getNameComponent(),
            $this->getSurnameComponent(),
            $this->getEmailComponent(),
        ])->columnSpan(2);

        return ($this->hasAvatars)
            ? [filament('filament-breezy')->getAvatarUploadComponent(), $groupFields]
            : [$groupFields];
    }
}
