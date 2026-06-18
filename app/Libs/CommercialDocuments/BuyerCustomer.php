<?php

namespace App\Libs\CommercialDocuments;

use Illuminate\Database\Eloquent\Model;
use LaravelDaily\Invoices\Classes\Buyer;

class BuyerCustomer
{
    public string $type;

    public Model $record;

    public function __construct($type, $record)
    {
        $this->type = $type;
        $this->record = $record;
    }

    public function createCustomer(): Buyer
    {
        if ($this->type == 'sale') {
            return new Buyer([
                'name' => $this->record->organization?->name ?? __('No Name'),
                'address' => $this->record->organization?->administrativeAddress?->address ?? __('No Address'),
                'custom_fields' => [
                    'zip' => $this->record->organization?->administrativeAddress?->zip ?? __('No Zip'),
                    'city' => $this->record->organization?->administrativeAddress?->city ?? __('No City'),
                    'country' => $this->record->organization?->administrativeAddress?->country ?? __('No Country'),
                    'state' => $this->record->organization?->administrativeAddress?->state ?? __('No State'),
                    'vat' => $this->record->organization?->vat ?? __('No Vat'),
                    'personal_id' => '#'.$this->record->organization?->getKey() ?? __('No  #Id'),
                    'email' => $this->record->organization?->primary_email ?? __('No Email'),
                ],
            ]);
        } elseif ($this->type == 'purchase') {
            return new Buyer([
                'name' => $this->record->vendor?->name ?? __('No Name'),
                'address' => $this->record->vendor?->administrativeAddress?->address ?? __('No Address'),
                'custom_fields' => [
                    'zip' => $this->record->vendor?->administrativeAddress?->zip ?? __('No Zip'),
                    'city' => $this->record->vendor?->administrativeAddress?->city ?? __('No City'),
                    'country' => $this->record->vendor?->administrativeAddress?->country ?? __('No Country'),
                    'state' => $this->record->vendor?->administrativeAddress?->state ?? __('No State'),
                    'vat' => $this->record->vendor?->vat ?? __('No Vat'),
                    'personal_id' => '#'.$this->record->vendor?->getKey() ?? __('No #Id'),
                    'email' => $this->record->vendor?->primary_email ?? __('No Email'),
                ],
            ]);
        }
    }
}
