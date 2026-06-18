<?php

namespace App\Traits\Columns;

use App\Filament\Clusters\MasterData\Resources\OrganizationResource;
use App\Filament\Clusters\MasterData\Resources\VendorResource;
use App\Models\Contact;
use App\Models\ModuleContact;
use Filament\Tables;
use Filament\Tables\Columns\Layout\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;
use Ysfkaya\FilamentPhoneInput\Tables\PhoneColumn;

trait HasColumns
{
    public static function organizationContact(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('organization.name')
            ->state(function ($record) {
                $contacts = $record->organization
                    ? $record->organization->contacts->map(function (ModuleContact $moduleContact) {
                        return Contact::where('id', $moduleContact->contact_id)->get()->pluck('full_name');
                    })
                    : new Collection;

                if ($contacts->isEmpty() && $record->contact) {
                    $contacts[] = [$record->contact->full_name];
                }

                return view('filament.tables.columns.organization-contact', [
                    'recordOrganizationName' => $record->organization->name ?? '',
                    'contacts' => $contacts,
                ]);
            })
            ->wrap()
            ->searchable()
            ->color('success')
            ->url(fn ($record) => $record?->organization_id ? OrganizationResource::getUrl('edit', ['record' => $record?->organization_id]) : '')
            ->label(__('Organization/Contact'));
    }

    public static function vendorContact(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('vendor.name')
            ->state(function ($record) {
                $contacts = $record->vendor
                    ? $record->vendor->contacts->map(function (ModuleContact $moduleContact) {
                        return Contact::where('id', $moduleContact->contact_id)->get()->pluck('full_name');
                    })
                    : new Collection;

                if ($contacts->isEmpty() && $record->contact) {
                    $contacts[] = [$record->contact->full_name];
                }

                return view('filament.tables.columns.vendor-contact', [
                    'recordVendorName' => $record->vendor->name ?? '',
                    'contacts' => $contacts,
                ]);
            })
            ->wrap()
            ->searchable()
            ->color('success')
            ->url(fn ($record) => $record?->vendor_id ? VendorResource::getUrl('edit', ['record' => $record?->vendor_id]) : '')
            ->label(__('Vendor/Contact'));
    }

    public static function description(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('description')
            ->formatStateUsing(function ($record) {
                return view('filament.tables.columns.generic-description', [
                    'documents' => $record->attachments?->count() ?? null,
                    'description' => Str::limit($record->description, 90, '...'),
                ]);
            })
            ->label(__('Description'))
            ->searchable()
            ->tooltip(fn ($record) => $record->description)
            ->wrap();
    }

    public static function linkedUser(string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('linkedUser.fullName')
            ->formatStateUsing(function ($record) {
                return view('filament.tables.columns.generic-linked-user', [
                    'documents' => $record->attachments?->count() ?? null,
                    'linkedUser' => $record->linkedUser,
                ]);
            })
            ->label(__($label))
            ->searchable()
            ->wrap();
    }

    public static function members(): Tables\Columns\ImageColumn
    {
        return Tables\Columns\ImageColumn::make('members')
            ->label(__('Members'))
            ->state(function ($record): array {
                $avatars = [];

                // Corrected the typo from $record->member to $record->members
                if ($record->members) {
                    foreach ($record->members as $member) {
                        $user = $member->user;

                        if (! $user) {
                            continue;
                        }

                        if ($user->avatar_url) {
                            $avatars[] = url('storage/'.$user->avatar_url);
                        } else {
                            // Fallback avatar using user initials, custom colors and high resolution
                            $avatars[] = 'https://ui-avatars.com/api/?name='.urlencode($user->name.' '.$user->surname).'&background=EBF4FF&color=7F9CF5&size=128';
                        }
                    }
                }

                return $avatars;
            })
            ->tooltip(function ($record): string|View {
                if ($record->members && $record->members->isNotEmpty()) {
                    return view('filament.tables.columns.members-list-tooltip', ['record' => $record]);
                }

                return '';
            })
            ->limitedRemainingText()
            ->stacked()
            ->ring(2)
            ->overlap(4)
            ->circular()
            ->visible(fn () => auth()->user()->can('manageMember', static::getModel()));
    }

    public static function user($description = null): Tables\Columns\TextColumn
    {
        if (! $description) {
            return Tables\Columns\TextColumn::make('user.fullName')
                ->label(__('resources.UserResource'))
                ->wrap();
        } else {
            return Tables\Columns\TextColumn::make('user.fullName')
                ->description($description)
                ->label(__('resources.UserResource'))
                ->wrap();
        }
    }

    public static function team(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('team.name')
            ->hidden(! auth()->user()->hasRole(['super_admin']))
            ->label(__('resources.TeamResource'))
            ->wrap()
            ->sortable();
    }

    /**
     * Summary of phone
     */
    public static function phone(string $fieldName, string $label): PhoneColumn
    {
        return PhoneColumn::make($fieldName)
            ->displayFormat(PhoneInputNumberType::INTERNATIONAL)
            ->searchable()
            ->label(__($label));
    }

    /**
     * Summary of dateColumn
     */
    public static function dateColumn(string $fieldName, string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($fieldName)
            ->searchable()
            ->sortable()
            ->wrap()
            ->date(auth()->user()->date_format ?? 'd/m/Y')
            ->label(__($label));
    }

    public static function dateTimeColumn(string $fieldName, string $label): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make($fieldName)
            ->searchable()
            ->sortable()
            ->wrap()
            ->date(auth()->user()->date_format ? auth()->user()->date_format.' H:m:s' : 'd/m/Y H:m:s')
            ->label(__($label));
    }

    public static function filePreviewTable($name, $label, $width): Tables\Columns\ViewColumn
    {
        return Tables\Columns\ViewColumn::make($name)
            ->label($label)
            ->state(function ($record) {
                return $record->attachments;
            })
            ->view('filament.tables.columns.file-preview', [
                'width' => $width,
            ]);
    }

    public static function filePreviewTableAttachments($name, $label, $width = 150): Tables\Columns\ViewColumn
    {
        return Tables\Columns\ViewColumn::make($name)
            ->label($label)
            ->state(function ($record) {
                return [$record];
            })
            ->alignCenter()
            ->view('filament.tables.columns.file-preview', [
                'width' => $width,
            ]);
    }
}
