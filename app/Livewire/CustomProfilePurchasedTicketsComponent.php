<?php

namespace App\Livewire;

use App\Libs;
use App\Libs\TicketService;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketPackage;
use App\Models\Ticket\UserTicketPackage;
use Carbon\Carbon;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\HtmlString;
use Jeffgreco13\FilamentBreezy\Livewire\MyProfileComponent;

class CustomProfilePurchasedTicketsComponent extends MyProfileComponent implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'livewire.custom-profile-purchased-tickets-component';

    public array $data;

    public static function table(Table $table): Table
    {
        return $table
            ->paginated([5])
            ->query(UserTicketPackage::query()->where('user_id', '=', auth()->user()->getKey()))
            ->recordClasses(fn (Model $record) => TicketService::getCssStatus($record))
            ->columns([
                TextColumn::make('id')
                    ->label(__('#Id'))
                    ->visible(fn () => auth()->user()->hasRole(['super_admin']))
                    ->sortable(),
                TextColumn::make('ticketPackage')
                    ->label(__('profile.Package'))
                    ->wrap()
                    ->formatStateUsing(fn (UserTicketPackage $record) => $record?->ticketPackage?->name)
                    ->description(fn (UserTicketPackage $record) => $record?->ticketPackage?->description),
                TextColumn::make('quantity')
                    ->alignRight()
                    ->label(__('ticket.Quantity'))
                    ->summarize([
                        Sum::make()
                            ->label(__('')),
                    ]),
                TextColumn::make('ticket_package_price')
                    ->state(function (UserTicketPackage $record): float|int {
                        return $record->quantity * $record?->ticketPackage?->price;
                    })
                    ->description(function (UserTicketPackage $record): string {
                        return __('Unit').': '.$record?->ticketPackage?->price;
                    })
                    ->prefix(Libs\UserService::getCurrencyPrefix())
                    ->alignRight()
                    ->wrap()
                    ->label(__('profile.Package price'))
                    ->summarize(Summarizer::make()
                        ->label('')
                        ->using(function (Builder $query): float|int {
                            $userTicketPackages = $query->whereUserId(auth()->user()->getKey())->get();
                            $summary = 0;

                            foreach ($userTicketPackages as $userTicketPackage) {
                                $package = TicketPackage::whereId($userTicketPackage->ticket_package_id)->first();
                                $summary += $package?->price * $userTicketPackage->quantity;
                            }

                            return $summary;

                        })->money(auth()->user()->currency)),
                TextColumn::make('ticket_package_quantity')
                    ->state(function (UserTicketPackage $record, TicketService $ticketService): float|int {
                        return $ticketService::getUserTotalTicketPackage($record);
                    })
                    ->description(function (UserTicketPackage $record): string {
                        return __('ticket.Tickets in Package').': '.$record?->ticketPackage?->ticket_quantity;
                    })
                    ->label(__('ticket.Ticket Quantity'))
                    ->alignRight()
                    ->wrap()
                    ->summarize(Summarizer::make()
                        ->label('')
                        ->using(function (Builder $query): float|int {
                            $userTicketPackages = $query->whereUserId(auth()->user()->getKey())->get();
                            $ticketQuantities = 0;

                            foreach ($userTicketPackages as $userTicketPackage) {
                                $package = TicketPackage::whereId($userTicketPackage->ticket_package_id)->first();
                                $ticketQuantities += $package?->ticket_quantity * $userTicketPackage->quantity;
                            }

                            return $ticketQuantities;

                        })),
                TextColumn::make('ticketPackage.duration')
                    ->extraHeaderAttributes([
                        'class' => 'min-w-48',
                    ])
                    ->label(__('ticket.Package Tickets Duration'))
                    ->alignCenter()
                    ->description(function (UserTicketPackage $record, TicketService $ticketService): HtmlString {
                        $duration = $ticketService::getUserPackageExpireRemain($record);

                        return new HtmlString(view('livewire.custom-profile-purchased-tickets-component-duration', [
                            'purchasedOn' => Carbon::parse($record->created_at)->format('d-m-Y'),
                            'expire' => $duration['expire']->format('d-m-Y'),
                            'remain' => $duration['remain'],
                        ]));
                    }),
                TextColumn::make('price_per_ticket')
                    ->state(function (UserTicketPackage $record): float|int {
                        return $record?->ticketPackage?->price_per_ticket ?? 0;
                    })
                    ->prefix(Libs\UserService::getCurrencyPrefix())
                    ->alignRight()
                    ->label(__('ticket.Price per Ticket')),
                TextColumn::make('tickets_cost_per_intervention')
                    ->state(function (UserTicketPackage $record): float|int {
                        $package = $record?->ticketPackage;

                        return Ticket::where('user_id', auth()->user()->getKey())
                            ->where('ticket_category_id', $package?->ticket_category_id)
                            ->whereNotNull('ticket_intervention_id')
                            ->count();
                    })
                    ->description(function (UserTicketPackage $record): HtmlString {
                        $package = $record?->ticketPackage;

                        $interventions = Ticket::where('user_id', $record->user_id)
                            ->where('ticket_category_id', $package?->ticket_category_id)
                            ->whereNotNull('ticket_intervention_id')
                            ->count();

                        $ticketsIntervention = $record?->ticketPackage?->tickets_cost_per_intervention;

                        return new HtmlString('x '.__('ticket.Tickets cost per Intervention').': '.$ticketsIntervention.'<br />'.__('ticket.Tickets Consumed').': '.$interventions * $ticketsIntervention);
                    })
                    ->alignRight()
                    ->label(__('ticket.Tickets Requested')),
                TextColumn::make('tickets_remains')
                    ->state(function (UserTicketPackage $userTicketPackage, TicketService $ticketService): float|int {
                        return $ticketService::getUserProfileTotalTicketPackage($userTicketPackage);
                    })
                    ->alignRight()
                    ->label(__('ticket.Remain'))
                    ->summarize(Summarizer::make()
                        ->label('')
                        ->using(function (TicketService $ticketService): float|int {
                            $userTicketPackages = auth()->user()->ticketPackages;

                            $total = 0;
                            foreach ($userTicketPackages as $userTicketPackage) {
                                $userTotalTicketPackage = $ticketService::getUserProfileTotalTicketpackage($userTicketPackage);

                                $total += $userTotalTicketPackage;
                            }

                            return $total;
                        })),
            ]);
    }
}
