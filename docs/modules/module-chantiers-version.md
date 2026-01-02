# Module Chantiers - Note de Version

**Version :** 1.1
**Date :** 2024-05-21

---

## Fonctionnalités Clés

Le module Chantiers est le cœur de la gestion de projets dans Batistack. Il permet un suivi complet des opérations, des coûts et de la rentabilité.

- **Gestion de Projet** : Création et suivi des chantiers avec informations détaillées (adresse, dates, client).
- **Suivi des Coûts** : Agrégation automatique des coûts provenant de plusieurs sources :
    - Main d'œuvre (via le module Pointage/RH).
    - Achats de matériaux (via les Factures Fournisseurs).
    - Locations de matériel (via le module Locations).
    - Coûts de la flotte de véhicules (TCO via le module Flottes).
- **Suivi Budgétaire** : Définition d'un budget détaillé (par poste de coût) et comparaison en temps réel avec les dépenses réelles.
- **Reporting** : Génération de rapports de rentabilité par chantier (format PDF/CSV).
- **Géolocalisation** : Conversion automatique des adresses en coordonnées GPS.

## Dernières Améliorations (v1.1)

- **Alertes Budgétaires Automatiques** : Un système de notification a été mis en place pour alerter les administrateurs lorsque les coûts réels d'un chantier atteignent **80%** ou **100%** du budget alloué.
- **Historisation des Révisions Budgétaires** : Il est désormais possible de "photographier" l'état d'un budget à un instant T. Cela permet de suivre l'évolution des prévisions (Budget Initial vs. Avenants) et d'analyser les dérives.

## Points Techniques & Dépendances

- **Modèles Principaux** :
    - `app/Models/Chantiers/Chantiers.php`
    - `app/Models/Chantiers/ChantierBudgetVersion.php` (Nouveau)
- **Logique Métier & Automatisation** :
    - `app/Observers/RH/TimesheetObserver.php` (pour l'imputation des coûts de main d'œuvre).
    - `app/Jobs/Fleets/AllocateFleetCostsJob.php` (pour l'imputation des coûts de flotte).
    - `app/Console/Commands/Chantiers/CheckChantierBudgetsCommand.php` (Nouveau, pour les alertes).
- **Dépendances Fortes** :
    - Module **Pointage/RH** (pour les coûts de main d'œuvre).
    - Module **Flottes** (pour les coûts de véhicules).
    - Module **Facturation** (pour les revenus et coûts d'achats).
