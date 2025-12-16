# Batistack - ERP Modulaire pour le BTP

Batistack est une solution ERP (SAAS) modulaire conçue spécifiquement pour les entreprises du secteur de la construction (BTP). Le projet est développé avec le framework Laravel 12 et utilise Filament PHP pour l'ensemble de son interface d'administration.

L'architecture est pensée pour être multi-tenant, permettant à chaque entreprise cliente de gérer ses données de manière isolée et sécurisée.

## ✨ Fonctionnalités Clés

Le projet s'articule autour de plusieurs modules métiers, certains étant déjà stables et d'autres en cours de développement.

### Modules Stables

- **CRM (Tiers)** : Gestion des clients, fournisseurs et sous-traitants.
- **Chantiers** : Suivi des projets, incluant la gestion des coûts de main-d'œuvre qui sont mis à jour automatiquement via les fiches de pointage.
- **Articles & Stock** : Gestion du catalogue d'articles, des ouvrages (recettes) et du stock multi-dépôts.
- **Commerce & Facturation** : Création de devis, factures, acomptes et suivi des paiements.
- **Banque** : Gestion des comptes, synchronisation des transactions (via BridgeAPI) et rapprochement bancaire automatisé.
- **Pointage & RH** : Saisie des heures des employés et calcul du coût de la main-d'œuvre par chantier.
- **Notes de Frais** : Gestion des dépenses des employés avec un workflow de validation et une **comptabilisation automatique** après validation.
- **GED** : Gestion électronique des documents avec gestion de métadonnées et alertes d'expiration.

### Modules en Cours de Développement

- **Comptabilité** :
    - **Avancé** : Comptabilisation automatique des NDF, consommations Ulys, **factures de vente, factures fournisseurs et contrats de location**.
    - **Avancé** : Génération du Fichier des Écritures Comptables (FEC) avec gestion des tiers et numérotation séquentielle conforme.
    - **Avancé** : Reporting des journaux et Grand Livre, avec **génération automatique de rapports CSV**.
- **Paie** :
    - **Avancé** : Calcul des fiches de paie (agrégation heures/frais), **incluant les notes de frais remboursables et la gestion des heures majorées**.
    - **Avancé** : Génération d'exports CSV avec support de différents formats (Silae, Sage, générique).
- **Flottes** :
    - **Avancé** : Gestion détaillée des véhicules (immatriculation, type, marque, modèle, VIN, kilométrage).
    - **Avancé** : Gestion des assurances avec alertes d'expiration.
    - **Avancé** : Gestion des maintenances avec alertes d'échéance.
    - **Avancé** : Assignation des véhicules aux employés ou équipes, **avec suivi de statut et rappels de fin d'assignation**.
- **GPAO** :
    - **Avancé** : Gestion des ordres de fabrication, **incluant la création automatique à partir des commandes clients**, la planification, le suivi de statut, la mise à jour des stocks, le calcul du coût de la main-d'œuvre (automatisé via les pointages), et les notifications d'assignation et de retard.
- **Locations** :
    - **En cours** : Gestion des contrats de location (fournisseurs), avec calcul des totaux et comptabilisation automatique.

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
