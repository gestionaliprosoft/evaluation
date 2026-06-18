<x-filament-panels::page>
    <script>
      document.addEventListener("DOMContentLoaded", function(event) {
        const stripe = Stripe("{{ $this->stripeKey }}", { apiVersion: '2023-10-16' });

        checkStatus();

        async function checkStatus() {
          const clientSecret = new URLSearchParams(window.location.search).get(
            "payment_intent_client_secret"
          );

          if (!clientSecret) {
            return;
          }

          const { paymentIntent } = await stripe.retrievePaymentIntent(clientSecret);

          switch (paymentIntent.status) {
            case "succeeded":
                showMessage("{{ __('stripe.Payment succeeded!')}}");
                break;
            case "processing":
                showMessage("{{__('stripe.Your payment is processing.')}}");
                break;
            case "requires_payment_method":
                showMessage("{{__('stripe.Your payment was not successful, please try again.')}}");
                break;
            default:
                showMessage("{{__('stripe.Something went wrong.')}");
              break;
          }
        }

        // ------- UI helpers -------

        function showMessage(messageText) {
          const messageContainer = document.querySelector("#payment-message");

          messageContainer.classList.remove("hidden");
          messageContainer.textContent = messageText;
        }
      });
    </script>
    <div id="payment-message" class="hidden"></div>
</x-filament-panels::page>
