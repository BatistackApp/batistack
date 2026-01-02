# Module Flottes - Note de Version

**Version :** 1.1
**Date :** 2025-01-02

---

## Fonctionnalités Clés

Le module Flottes assure une gestion complète du parc de véhicules et d'équipements de l'entreprise.

- **Gestion de Parc** : Fiche détaillée pour chaque véhicule (immatriculation, marque, modèle, kilométrage, etc.).
- **Assignation** : Assignation des véhicules à des employés ou des équipes, avec détection de conflits pour éviter les doubles réservations.
- **Suivi des Expirations** : Alertes automatiques pour les assurances et les contrôles techniques arrivant à échéance.
- **Gestion des Maintenances** : Historique des entretiens (curatifs et préventifs) avec suivi des coûts.
- **Synchronisation Ulys** : Importation automatique des consommations de télépéage.
- **Imputation Analytique** : Un job quotidien alloue les coûts des véhicules aux chantiers sur lesquels ils ont été utilisés.

## Dernières Améliorations (v1.1)

- **Calcul du TCO (Coût Total de Possession)** : Le coût journalier interne (`internal_daily_cost`) de chaque véhicule est désormais calculé automatiquement. Ce calcul est plus précis et inclut :
    - La **dépréciation** du véhicule (basée sur son prix d'achat, sa valeur résiduelle et sa durée d'amortissement).
    - Le coût journalier des **assurances** actives.
    - Le coût moyen journalier des **maintenances** (basé sur l'historique des 12 derniers mois).
- **Flexibilité de la Dépréciation** : Il est maintenant possible de configurer la durée d'amortissement et la valeur résiduelle pour chaque véhicule.

## Points Techniques & Dépendances

- **Modèles Principaux** :
    - `app/Models/Fleets/Fleet.php`
    - `app/Models/Fleets/Insurance.php`
    - `app/Models/Fleets/Maintenance.php`
    - `app/Models/Fleets/FleetAssignment.php`
- **Logique Métier & Automatisation** :
    - `app/Jobs/Fleets/DepreciateFleetValueJob.php` (Amélioré pour le TCO).
    - `app/Jobs/Fleets/AllocateFleetCostsJob.php` (Utilise le TCO calculé).
    - `app/Console/Commands/Fleets/CheckFleetExpirationsCommand.php`.
- **Dépendances Fortes** :
    - Module **Chantiers** (pour l'imputation des coûts).
    - Module **Pointage/RH** (pour croiser les données d'utilisation).
