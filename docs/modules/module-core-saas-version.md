# Module Core / SaaS - Note de Version

**Version :** 1.1
**Date :** 2024-05-21

---

## Fonctionnalités Clés

Le module Core / SaaS est la fondation technique de Batistack. Il gère l'architecture multi-tenant, les abonnements et la modularité de la plateforme.

- **Multi-Tenancy** : Isolation complète des données entre les différentes entreprises clientes.
- **Gestion des Entreprises** : Création et configuration des comptes clients (tenants).
- **Gestion des Fonctionnalités (Features)** : Définition centralisée de toutes les fonctionnalités de l'application (Modules, Options, Services, Limites).
- **Gestion des Abonnements (Plans)** : Création de différents niveaux d'abonnements (ex: Essentiel, Pro) avec des fonctionnalités et des limites spécifiques.
- **Gestion des Souscriptions** : Assignation d'un plan à une entreprise pour une période donnée.

## Dernières Améliorations (v1.1)

- **Gestion des Quotas par Plan** : Il est désormais possible de définir des limites quantitatives pour chaque plan d'abonnement (ex: nombre maximum d'utilisateurs, de chantiers, espace de stockage).
- **Middleware de Vérification** : Un middleware `CheckQuota` a été créé pour bloquer automatiquement les actions (ex: création d'un nouvel utilisateur) si la limite de l'abonnement est atteinte. Ce middleware inclut une validation de sécurité pour s'assurer qu'il n'est utilisé que sur des modèles de données correctement scopés par entreprise.

## Points Techniques & Dépendances

- **Modèles Principaux** :
    - `app/Models/Core/Company.php`
    - `app/Models/Core/Feature.php`
    - `app/Models/Core/Plan.php`
    - `app/Models/Core/Subscription.php`
- **Logique Métier & Automatisation** :
    - `app/Enums/Core/TypeFeature.php` (Amélioré avec le type `LIMIT`).
    - `app/Http/Middleware/CheckQuota.php` (Nouveau).
    - `app/Trait/BelongsToCompany.php` (Trait fondamental pour le multi-tenant).
- **Dépendances Fortes** :
    - Ce module est une dépendance centrale pour l'ensemble de l'application.
