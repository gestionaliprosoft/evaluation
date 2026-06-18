<?php

namespace App\Traits\RelationManagers;

use App\Filament\Clusters\Sales\Resources\SaleQuoteResource;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Sale\SaleQuote;
use App\Models\User;
use App\Services\ModuleSettingService;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

trait QuoteRelationManager
{
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return $ownerRecord->quotes->count();
    }

    public function form(Form $form): Form
    {
        return $form->schema(SaleQuoteResource::getFormsComponents());
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(SaleQuoteResource::getColumnsComponents())
            ->filters(SaleQuoteResource::getFiltersComponents())
            ->actions(array_merge(SaleQuoteResource::getActionsComponents(), [
                static::completeFormAction(SaleQuoteResource::class),
            ]))
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(auth()->user()->can('create', SaleQuote::class))
                    ->label(__('contact.Add Quote'))
                    ->modalHeading(__('contact.Add Quote'))
                    ->fillForm(fn (RelationManager $livewire): array => [
                        'date' => now(),
                        'contact_id' => $this->getContactId($livewire->ownerRecord, $livewire->ownerRecord::class),
                        'organization_id' => $this->getOrganizationId($livewire->ownerRecord, $livewire->ownerRecord::class),
                        'team_id' => $livewire->ownerRecord->team_id,
                        'user_id' => $livewire->ownerRecord->user_id,
                    ])
                    ->modalWidth(MaxWidth::Full)
                    ->createAnother(false)
                    ->mutateFormDataUsing(function ($data, ModuleSettingService $moduleSettingService) {
                        $data['team_id'] = Arr::has($data, 'team_id') ? $data['team_id'] : auth()->user()->team_id;
                        $data['uuid'] = Str::uuid();
                        $data['number_seq'] = SaleQuote::where('team_id', $data['team_id'])->orderBy('id', 'desc')->pluck('number_seq')->first() + 1;
                        $data['number'] = $moduleSettingService->getModuleSettings('Contacts', 'number').$data['number_seq'];

                        return $data;
                    }),
            ])
            ->bulkActions(SaleQuoteResource::getBulkActionsComponents());
    }

    protected function getContactId($ownerRecord, $ownerClass): ?int
    {
        return match ($ownerClass) {
            Contact::class => $ownerRecord->id,
            Organization::class => null,
            User::class => null,
        };
    }

    protected function getOrganizationId($ownerRecord, $ownerClass): ?int
    {
        return match ($ownerClass) {
            Contact::class => $ownerRecord->organization_id,
            Organization::class => $ownerRecord->id,
            User::class => null,
        };
    }
}
