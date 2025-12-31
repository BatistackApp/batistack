# Pistes d'Amélioration - Batistack

Ce document centralise les pistes d'amélioration et les refactorisations potentielles identifiées lors des phases de vérification des modules.

## 1. Module Core / SaaS

*   **Gestion des Abonnements (Plans)** :
    *   **[FAIT]** Ajouter une gestion plus fine des limites par plan (ex: nombre max d'utilisateurs, espace de stockage GED). *Implémenté via `TypeFeature::LIMIT`, `Company::checkQuota` et `CheckQuota` middleware.*
    *   Intégrer un système de paiement (Stripe/Paddle) pour la souscription automatique.
*   **Gestion des Fonctionnalités (Features)** :
    *   Permettre l'activation/désactivation dynamique des fonctionnalités pour un tenant sans redéploiement.
    *   Ajouter des dépendances entre fonctionnalités (ex: "GPAO" nécessite "Articles & Stock").

## 2. Module Comptabilité

*   **Export FEC** :
    *   **[FAIT]** Ajouter une validation préalable des écritures avant l'export (vérification de l'équilibre, des dates, des séquences). *Implémenté dans `GenerateFecJob::validateEntries`.*
    *   Permettre l'export partiel par période ou par journal.
*   **Reporting** :
    *   Ajouter des graphiques d'évolution des charges/produits dans le tableau de bord comptable.
    *   Permettre la comparaison N vs N-1 pour le Grand Livre.

## 3. Module Chantiers

*   **Suivi Budgétaire** :
    *   **[FAIT]** Ajouter des alertes automatiques lorsque le coût réel dépasse un certain pourcentage du budget (ex: 80%, 100%). *Implémenté via `CheckChantierBudgetsCommand` et `BudgetAlertNotification`.*
    *   Permettre la révision budgétaire avec historisation des versions.
*   **Planification** :
    *   Intégrer une vue Gantt pour la planification des chantiers et des ressources.

## 4. Module Flottes

*   **Géolocalisation** :
    *   Intégrer une API de tracking GPS en temps réel pour les véhicules.
    *   Calculer automatiquement les kilomètres parcourus pour les comparer aux relevés manuels.
*   **Analyse TCO** :
    *   Affiner le calcul du TCO (Total Cost of Ownership) en incluant la dépréciation du véhicule.

## 5. Module GPAO

*   **Planification de Production** :
    *   Ajouter un algorithme d'ordonnancement automatique des OF en fonction de la charge des ressources.
    *   Gérer les contraintes de capacité des machines/postes de travail.
*   **Qualité** :
    *   Ajouter un module de contrôle qualité à différentes étapes de la production.

## 6. Module 3D Vision

*   **Visualisation** :
    *   Intégrer Autodesk Forge ou OpenProject BIM pour la visualisation des maquettes IFC directement dans le navigateur.
    *   Permettre l'annotation des maquettes 3D (Bussiness Issues) et la création de tâches liées.

## 7. Architecture Globale

*   **Performance** :
    *   Mettre en place du caching (Redis) pour les requêtes lourdes (ex: calculs de rentabilité, tableaux de bord).
    *   Optimiser les requêtes Eloquent (Eager Loading) pour éviter le problème N+1.
*   **Tests** :
    *   Augmenter la couverture de tests unitaires et fonctionnels (Pest/PHPUnit), notamment sur les calculs critiques (Paie, Compta).
*   **API** :
    *   Documenter l'API REST pour les intégrations tierces (Swagger/OpenAPI).
