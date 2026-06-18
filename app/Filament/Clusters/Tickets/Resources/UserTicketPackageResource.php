<?php

namespace App\Filament\Clusters\Tickets\Resources;

use App\Filament\Clusters\Tickets;
use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\Pages;
use App\Filament\Clusters\Tickets\Resources\UserTicketPackageResource\RelationManagers;
use App\Filament\Tables\Actions\ActivityAction;
use App\Libs\FormService;
use App\Libs\TicketService;
use App\Libs\UserTicketService;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketPackage;
use App\Models\Ticket\UserTicketPackage;
use App\Traits\BaseSettings;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\HtmlString;

class UserTicketPackageResource extends Resource
{
    use BaseSettings;

    protected static ?string $model = UserTicketPackage::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = Tickets::class;

    public static function form(Form $form): Form
    {
        return static::defineForm($form);
    }

    public static function table(Table $table): Table
    {
        return static::defineTable($table)
            ->recordClasses(fn (Model $record) => TicketService::getCssStatus($record))
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserTicketPackages::route('/'),
            'create' => Pages\CreateUserTicketPackage::route('/create'),
            'view' => Pages\ViewUserTicketPackage::route('/{record}'),
            'edit' => Pages\EditUserTicketPackage::route('/{record}/edit'),
            'activities' => Pages\ListUserTicketPackageActivities::route('/{record}/activities'),
        ];
    }

    public static function getColumnsComponents(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')
                ->label(__('#Id'))
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            self::user(fn (UserTicketPackage $userTicketPackage) => 'Team: '.$userTicketPackage->team?->name),
            Tables\Columns\TextColumn::make('ticket_package_id')
                ->label(__('profile.Package'))
                ->wrap()
                ->formatStateUsing(fn (UserTicketPackage $record) => $record->ticketPackage?->name)
                ->description(fn (UserTicketPackage $record) => $record->ticketPackage?->description),
            Tables\Columns\TextColumn::make('quantity')
                ->alignRight()
                ->label(__('Quantity'))
                ->summarize([
                    Sum::make()
                        ->label(__('profile.Ticket packages total Quantity')),
                ]),

            Tables\Columns\TextColumn::make('ticket_package_price')
                ->state(function (UserTicketPackage $record): float|int {
                    return $record->quantity * $record->ticketPackage?->price;
                })
                ->description(function (UserTicketPackage $record): string {
                    return __('Unit').': '.$record->ticketPackage?->price;
                })
                ->prefix(Libs\UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('profile.Package price'))
                ->summarize(Summarizer::make()
                    ->label('')
                    ->using(function (Builder $query): float|int {
                        $userTicketPackages = $query->get();
                        $summary = 0;

                        foreach ($userTicketPackages as $userTicketPackage) {
                            $package = TicketPackage::whereId($userTicketPackage->ticket_package_id)->first();
                            $summary += $package?->price * $userTicketPackage->quantity;
                        }

                        return $summary;

                    })->money(auth()->user()->currency)),
            Tables\Columns\TextColumn::make('ticket_package_quantity')
                ->state(function (UserTicketPackage $record): float|int {
                    return $record->ticketPackage?->ticket_quantity * $record->quantity;
                })
                ->description(function (UserTicketPackage $record): string {
                    return __('ticket.Tickets in Package').': '.$record->ticketPackage?->ticket_quantity;
                })
                ->label(__('ticket.Ticket Quantity'))
                ->alignRight()
                ->summarize(Summarizer::make()
                    ->label('')
                    ->using(function (Builder $query): float|int {
                        $userTicketPackages = $query->get();
                        $ticketQuantities = 0;

                        foreach ($userTicketPackages as $userTicketPackage) {
                            $package = TicketPackage::whereId($userTicketPackage->ticket_package_id)->first();
                            $ticketQuantities += $package?->ticket_quantity * $userTicketPackage->quantity;
                        }

                        return $ticketQuantities;

                    })),
            Tables\Columns\TextColumn::make('ticketPackage.duration')
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
            Tables\Columns\TextColumn::make('ticketPackage.price_per_ticket')
                ->prefix(Libs\UserService::getCurrencyPrefix())
                ->alignRight()
                ->label(__('ticket.Price per Ticket')),
            Tables\Columns\TextColumn::make('tickets_requested')
                ->state(function (UserTicketPackage $record) {
                    $package = $record->ticketPackage;

                    return Ticket::where('user_id', $record->user_id)
                        ->where('ticket_category_id', $package?->ticket_category_id)
                        ->whereNotNull('ticket_intervention_id')
                        ->count();

                })
                ->description(function (UserTicketPackage $record): HtmlString {
                    $package = $record->ticketPackage;

                    $interventions = Ticket::where('user_id', $record->user_id)
                        ->where('ticket_category_id', $package?->ticket_category_id)
                        ->whereNotNull('ticket_intervention_id')
                        ->count();

                    $ticketsIntervention = $record->ticketPackage?->tickets_cost_per_intervention;

                    return new HtmlString('x '.__('ticket.Tickets cost per Intervention').': '.$ticketsIntervention.'<br />'.__('ticket.Tickets Consumed').': '.$interventions * $ticketsIntervention);
                })
                ->alignRight()
                ->label(__('ticket.Tickets Requested')),
            Tables\Columns\TextColumn::make('tickets_remains')
                ->state(function (UserTicketPackage $userTicketPackage, TicketService $ticketService): float|int {
                    return $ticketService::getUserProfileTotalTicketpackage($userTicketPackage);
                })
                ->alignRight()
                ->label(__('ticket.Remain'))
                ->summarize(Summarizer::make()
                    ->label('')
                    ->using(function (Builder $query, TicketService $ticketService): float|int {
                        $userTicketPackages = $query->get();

                        $total = 0;
                        foreach ($userTicketPackages as $userTicketPackage) {
                            $userTotalTicketPackage = $ticketService::getUserProfileTotalTicketpackage(new UserTicketPackage((array) $userTicketPackage));

                            $total += $userTotalTicketPackage;
                        }

                        return $total;
                    })),
        ];
    }

    public static function getFiltersComponents(): array
    {
        return [
            static::teamFilter(),
            static::userFilter(),
        ];
    }

    public static function getActionsComponents(): array
    {
        return [
            self::viewAction(),
            self::editAction(),
            Tables\Actions\ActionGroup::make([
                Tables\Actions\DeleteAction::make()
                    ->label(__('Delete'))
                    ->after(fn () => redirect(self::getUrl('index'))),
                ActivityAction::make('activities'),
            ]),
        ];
    }

    public static function getBulkActionsComponents(): array
    {
        return [
            Tables\Actions\BulkActionGroup::make([
                self::changeRecordOwnership(),
                self::deleteBulkAction(),
            ]),
        ];
    }

    public static function getFormsComponents(): array
    {
        return [
            Forms\Components\Grid::make(12)->schema([
                // Left side: Main form gets 9 columns out of 12 on large screens
                Forms\Components\Group::make([
                    Section::make()->schema([
                        Forms\Components\Select::make('ticket_package_id')
                            ->label(__('ticket.Ticket Package'))
                            ->options(fn (Get $get) => TicketPackage::getOptionsForSelect($get('ticket_package_id')))
                            ->afterStateUpdated(function (Get $get, Set $set, UserTicketService $userTicketService, $operation) {
                                if ($operation == 'create' && $get('ticket_package_id')) {
                                    $userHasPackage = $userTicketService->checkIfUserHasPackage($get('user_id'), $get('ticket_package_id'));

                                    if ($userHasPackage) {
                                        Notification::make()
                                            ->danger()
                                            ->title(__('support.User Has package Error'))
                                            ->body(__('support.User Has package Error Message'))
                                            ->persistent()
                                            ->send();

                                        $set('ticket_package_id', '');
                                    }
                                }
                            })
                            ->live()
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])->columns(3),
                ])->columnSpan(['default' => 12, 'lg' => 9]),

                // Right side: Sidebar gets only 3 columns out of 12 on large screens
                Forms\Components\Group::make([
                    FormService::assignedFormSection(),
                    FormService::timestamps(),
                ])->columnSpan(['default' => 12, 'lg' => 3]),
            ]),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ActivitiesRelationManager::class,
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('navigations.group.tickets');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigations.label.user_ticket_packages');
    }
}
