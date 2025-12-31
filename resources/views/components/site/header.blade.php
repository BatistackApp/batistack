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
                    <div class="nav-item group py-7 cursor-pointer border-b-2 border-transparent hover:border-ovh-blue">
                        <span class="font-medium text-ovh-dark uppercase text-sm tracking-wider">Nos Solutions</span>
                        <div class="mega-menu p-8">
                            <div class="container mx-auto grid grid-cols-4 gap-8">
                                <div>
                                    <h3 class="font-bold text-ovh-blue mb-4 uppercase text-xs tracking-widest">Gestion Chantier</h3>
                                    <ul class="space-y-2 text-sm">
                                        <li class="hover:text-ovh-blue"><a href="#">Planning & Ressources</a></li>
                                        <li class="hover:text-ovh-blue"><a href="#">Suivi de consommation</a></li>
                                        <li class="hover:text-ovh-blue"><a href="#">Rapports d'intervention</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h3 class="font-bold text-ovh-blue mb-4 uppercase text-xs tracking-widest">Finance & Devis</h3>
                                    <ul class="space-y-2 text-sm">
                                        <li class="hover:text-ovh-blue"><a href="#">Devis & Facturation</a></li>
                                        <li class="hover:text-ovh-blue"><a href="#">Calcul de rentabilité</a></li>
                                        <li class="hover:text-ovh-blue"><a href="#">Paiements Chorus Pro</a></li>
                                    </ul>
                                </div>
                                <div>
                                    <h3 class="font-bold text-ovh-blue mb-4 uppercase text-xs tracking-widest">Ressources Humaines</h3>
                                    <ul class="space-y-2 text-sm">
                                        <li class="hover:text-ovh-blue"><a href="#">Pointage Mobile</a></li>
                                        <li class="hover:text-ovh-blue"><a href="#">Gestion des EPI</a></li>
                                        <li class="hover:text-ovh-blue"><a href="#">Notes de frais</a></li>
                                    </ul>
                                </div>
                                <div class="bg-blue-50 p-4 rounded">
                                    <h3 class="font-bold text-ovh-blue mb-2">Nouveauté : Module BIM</h3>
                                    <p class="text-xs text-gray-600 mb-4">Intégrez vos maquettes 3D directement dans votre suivi de chantier.</p>
                                    <a href="#" class="text-sm font-bold text-ovh-blue hover:underline">En savoir plus →</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a href="#" class="font-medium text-ovh-dark uppercase text-sm tracking-wider py-7 border-b-2 border-transparent hover:border-ovh-blue">Tarifs</a>
                    <a href="#" class="font-medium text-ovh-dark uppercase text-sm tracking-wider py-7 border-b-2 border-transparent hover:border-ovh-blue">Écosystème</a>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <button class="px-6 py-2 border-2 border-ovh-blue text-ovh-blue font-bold rounded hover:bg-blue-50 transition">Démo gratuite</button>
                <button class="px-6 py-2 bg-ovh-blue text-white font-bold rounded hover:bg-blue-700 transition">S'inscrire</button>
            </div>
        </div>
    </div>
</nav>
