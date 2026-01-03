<div>
    <script src="https://js.stripe.com/clover/stripe.js"></script>

    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">
                Créez votre espace Batistack
            </h2>
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-400">
                Commencez dès maintenant à gérer vos chantiers simplement.
            </p>
        </div>

        <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <form wire:submit="create">
                {{ $this->form }}
            </form>
        </div>
    </div>
</div>
