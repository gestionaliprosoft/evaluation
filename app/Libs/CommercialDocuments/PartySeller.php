<?php

namespace App\Libs\CommercialDocuments;

use Illuminate\Database\Eloquent\Model;
use LaravelDaily\Invoices\Classes\Party;

class PartySeller
{
    public string $type;

    public Model $record;

    public function __construct($type, $record)
    {
        $this->type = $type;
        $this->record = $record;
    }

    public function createSeller(): Party
    {
        return new Party([
            'name' => auth()->user()->team->business_name,
            'address' => auth()->user()->team->address,
            'custom_fields' => [
                'zip' => auth()->user()->team->zip,
                'city' => auth()->user()->team->city,
                'country' => auth()->user()->team->country,
                'state' => auth()->user()->team->state,
                'vat' => auth()->user()->team->vat ?? '',
                'personal_id' => '',
                'email' => auth()->user()->team->email,
            ],
        ]);
    }
}
