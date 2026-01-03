<div wire:ignore x-data="{
    stripe: null,
    elements: null,
    card: null,
    errorMessage: '',
    mounted: false,

    async initStripe() {
        if (this.mounted) return;

        try {
            if (typeof Stripe === 'undefined') {
                throw new Error('Le service de paiement est inaccessible. Veuillez désactiver votre bloqueur de publicité.');
            }

            let checkVisibility = setInterval(() => {
                const el = document.getElementById('card-element');
                if (el && el.offsetParent !== null) {
                    clearInterval(checkVisibility);
                    this.mountStripe(el);
                }
            }, 100);

        } catch (error) {
            this.errorMessage = error.message;
            console.error('Stripe Init Error:', error);
        }
    },

    mountStripe(el) {
        this.stripe = Stripe('{{ config('cashier.key') }}');
        this.elements = this.stripe.elements();

        const style = {
            base: {
                color: '#32325d',
                fontFamily: 'Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif',
                fontSmoothing: 'antialiased',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#fa755a',
                iconColor: '#fa755a'
            }
        };

        this.card = this.elements.create('card', {style: style});
        this.card.mount('#card-element');
        this.mounted = true;

        this.card.on('change', (event) => {
            this.errorMessage = event.error ? event.error.message : '';
        });
    },

    async processPayment() {
        if (!this.stripe || !this.card) {
            this.errorMessage = 'Le formulaire de paiement n\'est pas initialisé.';
            return false;
        }

        try {
            // Utilisation de createPaymentMethod au lieu de confirmCardSetup
            // Cela ne nécessite pas de clientSecret serveur
            const { paymentMethod, error } = await this.stripe.createPaymentMethod({
                type: 'card',
                card: this.card,
                billing_details: {
                    name: $wire.data.card_holder
                }
            });

            if (error) {
                this.errorMessage = error.message;
                return false;
            } else {
                // On envoie le PaymentMethod ID au composant Livewire
                $wire.set('data.payment_method_id', paymentMethod.id);
                return true;
            }
        } catch (e) {
            this.errorMessage = 'Une erreur est survenue lors du traitement du paiement.';
            console.error(e);
            return false;
        }
    }
}" x-init="initStripe()">

    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Informations de paiement</label>
        <div id="card-element" class="w-full p-3 border border-gray-300 rounded-md shadow-sm bg-white dark:bg-gray-700 dark:border-gray-600 min-h-[3.5rem]">
            <!-- Stripe Element will be inserted here -->
        </div>
        <div x-show="errorMessage" x-text="errorMessage" class="mt-2 text-sm text-red-600 font-semibold"></div>
    </div>

    <button type="button"
            x-on:click="if(await processPayment()) { $wire.create(); }"
            class="w-full bg-primary-600 hover:bg-primary-500 text-white font-bold py-2 px-4 rounded flex justify-center items-center transition-colors duration-200">
        <span wire:loading.remove wire:target="create">Payer et S'inscrire</span>
        <span wire:loading wire:target="create">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Traitement en cours...
        </span>
    </button>
</div>
