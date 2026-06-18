<x-filament-panels::page>
    <script>
      document.addEventListener("DOMContentLoaded", function(event) {
        const stripe = Stripe("{{ $this->stripeKey }}", { apiVersion: '2023-10-16' });
        const elements = stripe.elements({ clientSecret: '{{ $clientSecret }}' });
        const paymentElementOptions = { layout: "tabs" };
        const paymentElement = elements.create("payment", paymentElementOptions);
        paymentElement.mount("#payment-element");

        document.querySelector("#payment-form").addEventListener("submit", handleSubmit);

        async function handleSubmit(e) {
          e.preventDefault();
          setLoading(true);

          const { error } = await stripe.confirmPayment({
            elements,
            confirmParams: {
              return_url: "{{ route('filament.admin.pages.payment-status') }}",
              receipt_email: "{{ auth()->user()->email }}",
            },
          });

          if (error.type === "card_error" || error.type === "validation_error") {
            showMessage(error.message);
          } else {
            showMessage(__('stripe.An unexpected error occurred.'));
          }

          setLoading(false);
        }

        function showMessage(messageText) {
          const messageContainer = document.querySelector("#payment-message");

          messageContainer.classList.remove("hidden");
          messageContainer.textContent = messageText;

          setTimeout(function() {
            messageContainer.classList.add("hidden");
            messageContainer.textContent = "";
          }, 4000);
        }

        function setLoading(isLoading) {
          if (isLoading) {
            document.querySelector("#submit").disabled = true;
            document.querySelector("#spinner").classList.remove("hidden");
            document.querySelector("#button-text").classList.add("hidden");
          } else {
            document.querySelector("#submit").disabled = false;
            document.querySelector("#spinner").classList.add("hidden");
            document.querySelector("#button-text").classList.remove("hidden");
          }
        }
      });
    </script>

    <x-filament::section aside>
        <x-slot name="heading">
            <x-filament::fieldset>
                <x-slot name="label">
                    {{ __('User') }}
                </x-slot>
                {{ auth()->user()->full_name }}, {{ auth()->user()->email }}
            </x-filament::fieldset>

            <x-filament::fieldset>
                <x-slot name="label">
                    {{ __('Items') }}
                </x-slot>
                {{ $this->items }}
            </x-filament::fieldset>
        </x-slot>

        <form id="payment-form" class="">
            <div id="payment-element">
                <!--Stripe.js injects the Payment Element-->
            </div>
            <x-filament::button id="submit" type="submit" class="mt-2" size="xl">
                <x-filament::loading-indicator class="h-5 w-5 hidden" id="spinner" />
                <span id="button-text">{{ __('stripe.Pay Now')}}</span>
            </x-filament::button>
            <div id="payment-message" class="hidden"></div>
        </form>
    </x-filament::section>

</x-filament-panels::page>
