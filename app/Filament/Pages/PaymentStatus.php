<?php

namespace App\Filament\Pages;

use App\Services\TeamService;
use Filament\Pages\Page;

class PaymentStatus extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.payment-status';

    public string $stripeKey;

    public string $stripeSecret;

    public string $stripeCurrency;

    protected static bool $shouldRegisterNavigation = false;

    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;

        $this->stripeKey = $this->teamService->getStripeKey(auth()->user());
        $this->stripeSecret = $this->teamService->getStripeSecret(auth()->user());
        $this->stripeCurrency = $this->teamService->getStripeCurrency(auth()->user());
    }
}
