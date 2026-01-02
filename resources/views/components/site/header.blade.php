<div class="bg-gray-100 py-1 text-xs border-b">
    <div class="container mx-auto px-4 flex justify-end space-x-4">
        <a href="#" class="hover:underline text-gray-600">Support 24/7</a>
        <a href="#" class="hover:underline text-gray-600">Documentation API</a>
        <a href="#" class="hover:underline text-gray-600 font-bold text-ovh-blue">Espace Client</a>
    </div>
</div>

<nav class="bg-white sticky top-0 z-50 border-b">
    <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-20">
            <div class="flex items-center space-x-10">
                <div class="text-3xl font-bold text-ovh-blue flex items-center">
                    <i class="fas fa-hard-hat mr-2"></i>{{ config('app.name') }}
                </div>

                <div class="hidden lg:flex space-x-8">
                    <a href="{{ route('catalog') }}" class="font-medium text-ovh-dark uppercase text-sm tracking-wider py-7 border-b-2 border-transparent hover:border-ovh-blue">Nos Solutions</a>
                    <a href="#" class="font-medium text-ovh-dark uppercase text-sm tracking-wider py-7 border-b-2 border-transparent hover:border-ovh-blue">Tarifs</a>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button class="px-6 py-2 border-2 border-ovh-blue text-ovh-blue font-bold rounded hover:bg-blue-50 transition">Démo gratuite</button>
                <button class="px-6 py-2 bg-ovh-blue text-white font-bold rounded hover:bg-blue-700 transition">S'inscrire</button>
            </div>
        </div>
    </div>
</nav>
