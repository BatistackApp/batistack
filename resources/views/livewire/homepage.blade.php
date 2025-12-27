<div>
    <header class="hero-gradient text-white py-20 overflow-hidden relative">
        <div class="container mx-auto px-4 flex flex-col lg:flex-row items-center">
            <div class="lg:w-1/2 z-10">
                <h1 class="text-5xl font-extrabold leading-tight mb-6">
                    L'ERP Cloud qui structure <br> la croissance du Bâtiment.
                </h1>
                <p class="text-xl mb-8 text-blue-100 max-w-xl">
                    Une solution modulaire, sécurisée et évolutive conçue avec Laravel 12 pour les PME et Grands Comptes du BTP. Gérez vos chantiers de la conception à la livraison.
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="bg-white text-ovh-blue px-8 py-4 rounded font-bold text-lg hover:bg-gray-100 transition shadow-lg">Lancer mon projet</a>
                    <a href="#" class="bg-transparent border border-white px-8 py-4 rounded font-bold text-lg hover:bg-white/10 transition">Voir les modules</a>
                </div>
            </div>
            <div class="lg:w-1/2 mt-12 lg:mt-0 relative">
                <div class="bg-white/10 backdrop-blur-md p-4 rounded-xl border border-white/20 shadow-2xl">
                    <img src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?auto=format&fit=crop&w=800&q=80" alt="Interface Dashboard" class="rounded shadow-inner opacity-90">
                </div>
                <!-- Floating badge -->
                <div class="absolute -bottom-6 -left-6 bg-white p-4 rounded shadow-xl text-ovh-dark">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 p-2 rounded-full text-green-600">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase">Hébergement Souverain</p>
                            <p class="text-sm">Données chiffrées en France</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- 2. Section des Fonctionnalités (Modules) --}}
    <!-- Modules Grid (OVH Style) -->
    <section class="py-20">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-ovh-dark mb-4">Une architecture modulaire à la carte</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Activez uniquement ce dont vous avez besoin. BuildCore évolue avec la taille de votre entreprise.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Module 1 -->
                <div class="bg-white p-8 rounded-lg card-shadow">
                    <div class="text-ovh-blue text-3xl mb-4"><i class="fas fa-file-invoice-dollar"></i></div>
                    <h3 class="text-xl font-bold mb-3">Devis & Facturation</h3>
                    <p class="text-gray-600 mb-6 text-sm">Gérez vos appels d'offres, créez des devis complexes avec bibliothèque d'ouvrages et suivez vos paiements.</p>
                    <ul class="text-sm space-y-2 mb-6 text-gray-700">
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Retenue de garantie</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Situations de travaux</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Signature électronique</li>
                    </ul>
                    <a href="#" class="text-ovh-blue font-bold hover:underline">Découvrir le module →</a>
                </div>

                <!-- Module 2 -->
                <div class="bg-white p-8 rounded-lg card-shadow">
                    <div class="text-ovh-blue text-3xl mb-4"><i class="fas fa-calendar-alt"></i></div>
                    <h3 class="text-xl font-bold mb-3">Planning de Chantier</h3>
                    <p class="text-gray-600 mb-6 text-sm">Visualisez la charge de vos équipes et l'avancement des travaux en temps réel sur des diagrammes de Gantt.</p>
                    <ul class="text-sm space-y-2 mb-6 text-gray-700">
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Synchronisation Outlook/Google</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Gestion des intempéries</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Notifications mobiles</li>
                    </ul>
                    <a href="#" class="text-ovh-blue font-bold hover:underline">Découvrir le module →</a>
                </div>

                <!-- Module 3 -->
                <div class="bg-white p-8 rounded-lg card-shadow">
                    <div class="text-ovh-blue text-3xl mb-4"><i class="fas fa-warehouse"></i></div>
                    <h3 class="text-xl font-bold mb-3">Stocks & Achats</h3>
                    <p class="text-gray-600 mb-6 text-sm">Maîtrisez vos approvisionnements et évitez les ruptures de stock sur vos chantiers stratégiques.</p>
                    <ul class="text-sm space-y-2 mb-6 text-gray-700">
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Bons de commande fournisseurs</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i> QR Code Scanning</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i> Inventaire tournant</li>
                    </ul>
                    <a href="#" class="text-ovh-blue font-bold hover:underline">Découvrir le module →</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Section -->
    <section class="bg-ovh-dark py-16 text-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col lg:flex-row items-center justify-between">
                <div class="mb-8 lg:mb-0">
                    <h2 class="text-3xl font-bold mb-2">Prêt à digitaliser votre entreprise ?</h2>
                    <p class="text-blue-200">Rejoignez plus de 1 200 entreprises du BTP qui nous font confiance.</p>
                </div>
                <div class="flex space-x-6">
                    <div class="text-center">
                        <div class="text-4xl font-extrabold">99.9%</div>
                        <div class="text-xs text-blue-300 uppercase">Disponibilité (SLA)</div>
                    </div>
                    <div class="border-l border-white/20 mx-4"></div>
                    <div class="text-center">
                        <div class="text-4xl font-extrabold">24/7</div>
                        <div class="text-xs text-blue-300 uppercase">Support technique</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 3. Section des Tarifs (Pricing) --}}
    <livewire:pricing-table />

</div>
