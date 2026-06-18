<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Models\Vendor;
use App\Traits\Columns\HasColumns;
use App\Traits\Filters\HasFilters;
use App\Traits\HasOptionalEnabledScope;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;
use Livewire\Component;

class RecordFinder extends Component implements HasForms, HasTable
{
    use HasColumns;
    use HasFilters;
    use HasOptionalEnabledScope;
    use InteractsWithForms;
    use InteractsWithTable;

    public $caller;

    public $fields;

    public $linkedId;

    public $dependant;

    public ?int $selectedRowId = null;

    public function table(Table $table): Table
    {
        $columns[] = TextColumn::make(app($this->caller)->getKeyName())->searchable();

        foreach ($this->fields as $field) {
            $columns[] = TextColumn::make($field)->label(__('record-finder.'.$field))->searchable();
        }

        $columns[] = static::team();

        return $table
            ->query(function () {
                if (auth()->user()->tenant?->name == config('app.main_tenant')) {
                    $query = auth()->user()->hasRole(['super_admin'])
                        ? $this->caller::query()
                        : $this->caller::query()->where('team_id', auth()->user()->team_id);
                } else {
                    $tenantId = auth()->user()->tenant_id;
                    $teamIds = User::where('tenant_id', $tenantId)->pluck('team_id')->unique();

                    if (auth()->user()->hasRole(['super_admin'])) {
                        $query = $this->caller::whereIn('team_id', $teamIds);
                    } else {
                        $query = $this->caller::whereIn('team_id', $teamIds)->where('id', auth()->user()->team_id);
                    }
                }

                if ($this->dependant && $this->linkedId) {
                    // dependant select extended search
                    $query = $this->dependantSelect($query);
                }

                return $query->onlyEnabled();
            })
            ->paginated(config('app.paginations.range'))
            ->recordClasses(fn (Model $record) => $record->id === $this->selectedRowId
                ? 'success-row text-white font-bold border-l-4'
                : ''
            )
            ->columns($columns)
            ->filters([
                static::teamFilter(),
            ])
            ->actions([
                Action::make('updateFinder')
                    ->label(__('Select'))
                    ->action(function ($record): void {
                        $this->dispatch('updateFinder', recordId: $record->getKey());
                    }),
            ])
            ->bulkActions([]);
    }

    public function render(): View
    {
        return view('livewire.record-finder');
    }

    #[On('updateFinder')]
    public function updateFinder($recordId)
    {
        $this->selectedRowId = $recordId;
        session(['recordId' => $recordId]);
    }

    protected function dependantSelect($query)
    {
        if ($this->caller == Organization::class) {
            // $this->linkedId contains contact_id
            // return only Contact linked to that Organization
            $contact = Contact::where('id', $this->linkedId)->with('organizations')->first();
            $organizationsIds = $contact->organizations->pluck('id');
            $query = $contact ? $query->whereIn('id', $organizationsIds) : [];
        } elseif ($this->caller == Vendor::class) {
            // $this->linkedId contains contact_id
            // return only Contact linked to that Vendord
            $contact = Contact::where('id', $this->linkedId)->with('vendors')->first();
            $vendorsIds = $contact->vendors->pluck('id');
            $query = $contact ? $query->whereIn('id', $vendorsIds) : [];
        } elseif ($this->caller == Contact::class) {
            // $this->linkedId contains organization_id or vendor_id
            // return only Dependant linked to that Contact
            $dependantRecord = $this->dependant::where('id', $this->linkedId)->first();
            $contactIds = [];
            foreach ($dependantRecord->contacts as $moduleContact) {
                $item = $moduleContact->contact;
                if ($item) {
                    $contactIds[] = $item->getKey();
                }
            }
            $query = $dependantRecord ? $query->whereIn('id', $contactIds) : [];
        }

        return $query;
    }
}
