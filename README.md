# Batistack - ERP Modulaire pour le BTP

Batistack est une solution ERP (SAAS) modulaire conçue spécifiquement pour les entreprises du secteur de la construction (BTP). Le projet est développé avec le framework Laravel 12 et utilise Filament PHP pour l'ensemble de son interface d'administration.

L'architecture est pensée pour être multi-tenant, permettant à chaque entreprise cliente de gérer ses données de manière isolée et sécurisée.

## ✨ Fonctionnalités Clés

Le projet s'articule autour de plusieurs modules métiers, dont la quasi-totalité est désormais fonctionnelle.

### Modules Terminés

- **CRM (Tiers)** : Gestion des clients, fournisseurs et sous-traitants.
- **Chantiers** : Suivi des projets, incluant la gestion complète des coûts (main-d'œuvre, location, achats, flotte) et le suivi budgétaire.
- **Articles & Stock** : Gestion du catalogue d'articles, des ouvrages (recettes) et du stock multi-dépôts.
- **Commerce & Facturation** : Création de devis, factures, acomptes et suivi des paiements.
- **Banque** : Gestion des comptes, synchronisation des transactions (via BridgeAPI) et rapprochement bancaire automatisé.
- **Pointage & RH** : Saisie des heures des employés et calcul du coût de la main-d'œuvre par chantier.
- **Notes de Frais** : Gestion des dépenses des employés avec un workflow de validation et une **comptabilisation automatique**.
- **GED** : Gestion électronique des documents avec gestion de métadonnées et alertes d'expiration.
- **Comptabilité** : Comptabilisation automatique de toutes les opérations, génération du FEC et reporting complet.
- **Paie** : Calcul des fiches de paie et génération d'exports CSV configurables (Silae, Sage, Generic).
- **Flottes** : Gestion complète du parc véhicules, incluant les assurances, maintenances, et l'**imputation analytique des coûts aux chantiers**.
- **GPAO** : Gestion des ordres de fabrication, planification, suivi de production et suggestions d'achats (MRP).
- **Locations** : Gestion des contrats de location fournisseurs avec **génération automatique des factures**.
- **Interventions** : Gestion des interventions avec suivi des coûts, facturation à la marge et comptabilisation.
- **Pilotage (KPI)** : Un service backend centralise les calculs de performance (rentabilité, alertes financières, taux d'utilisation).

### Modules en Cours

- **3D Vision** : La structure backend est prête pour la gestion des maquettes 3D (IFC/BIM). L'intégration d'un viewer est la prochaine étape.

## 🚀 Stack Technique

- **Framework** : Laravel 12
- **Interface d'Administration** : Filament PHP
- **Base de Données** : MySQL / PostgreSQL
- **Gestion des Fichiers** : Spatie Media Library
- **Déploiement & Environnement** : Laragon (pour le développement local)

## ⚙️ Installation (Développement)

Pour lancer le projet en local, suivez ces étapes :

1.  **Cloner le dépôt**
    ```bash
    git clone [URL_DU_DEPOT]
    cd batistack
    ```

2.  **Installer les dépendances**
    ```bash
    composer install
    npm install
    ```

3.  **Configurer l'environnement**
    - Copiez le fichier d'environnement : `cp .env.example .env`
    - Générez la clé d'application : `php artisan key:generate`
    - Configurez les variables de votre base de données dans le fichier `.env`.

4.  **Lancer les migrations et les seeders**
    ```bash
    php artisan migrate --seed
    ```

5.  **Compiler les assets**
    ```bash
    npm run dev
    ```

6.  **Lancer le serveur**
    Le projet est maintenant accessible via l'URL configurée dans votre environnement Laragon.
