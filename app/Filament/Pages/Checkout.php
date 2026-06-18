<?php

namespace App\Filament\Pages;

use App\Models\User;
use App\Services\TeamService;
use Filament\Pages\Page;
use NumberFormatter;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

class Checkout extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.checkout';

    protected $stripe;

    protected string $checkoutKey;

    public string $clientSecret;

    public string $stripeKey;

    public string $stripeSecret;

    public string $stripeCurrency;

    protected $parameters;

    protected $items;

    protected static bool $shouldRegisterNavigation = false;

    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;

        $this->stripeKey = $this->teamService->getStripeKey(auth()->user());
        $this->stripeSecret = $this->teamService->getStripeSecret(auth()->user());
        $this->stripeCurrency = $this->teamService->getStripeCurrency(auth()->user());

        $this->stripe = $this->stripeSecret ? new StripeClient($this->stripeSecret) : null;
    }

    public function mount(?array $parameters = null): void
    {
        $parameters['price'] = 15000;
        $parameters['uidd'] = 'abcd123-test';
        $parameters['item'] = 'Item Description test 2';

        $formatter = new NumberFormatter(app()->getLocale(), NumberFormatter::CURRENCY);

        $this->parameters = $parameters;
        $this->heading = __('Checkout');
        $this->items = $parameters['item'].': '.$formatter->formatCurrency($this->parameters['price'] / 100, 'eur');
        $this->subheading = __('stripe.Compile Form to Pay');
        $this->checkoutKey = 'checkout.'.$this->parameters['uidd'];
        $this->clientSecret = $this->getClientSecret();
    }

    protected function getClientSecret(): string
    {
        $user = auth()->user();
        $customer = $this->getStripeCustomer($user);
        $paymentIntent = $this->getPaymentIntent($customer);

        return $paymentIntent->client_secret;
    }

    protected function getStripeCustomer(User $user): Customer
    {
        if ($user->stripe_customer_id !== null) {
            return $this->stripe->customers->retrieve($user->stripe_customer_id);
        }

        $customer = $this->stripe->customers->create([
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer;
    }

    protected function getPaymentIntent(Customer $customer): PaymentIntent
    {
        $paymentIntentId = session($this->checkoutKey);

        if ($paymentIntentId === null) {
            return $this->createNewPaymentIntent($customer);
        }

        $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);

        if ($paymentIntent->status !== 'requires_payment_method') {
            return $this->createNewPaymentIntent($customer);
        }

        return $paymentIntent;
    }

    protected function createNewPaymentIntent(Customer $customer): PaymentIntent
    {
        $paymentIntent = $this->stripe->paymentIntents->create([
            'customer' => $customer->id,
            'setup_future_usage' => 'off_session',
            'amount' => $this->parameters['price'],
            'currency' => $this->stripeCurrency,
        ]);

        session([$this->checkoutKey => $paymentIntent->id]);

        return $paymentIntent;
    }
}
