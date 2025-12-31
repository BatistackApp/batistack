# Prochaines Fonctionnalités à Développer (NEXT_FEATURE)

Ce document liste les fonctionnalités prioritaires à développer pour les prochaines itérations du projet Batistack, basées sur l'état actuel des modules et les pistes d'amélioration identifiées.

## Priorité 1 : Finalisation du Module 3D Vision

**Objectif** : Permettre la visualisation des maquettes numériques (BIM/IFC) associées aux chantiers directement dans l'interface.

*   **Tâches** :
    *   [ ] Sélectionner et intégrer une librairie de visualisation 3D Web (ex: xeokit-sdk, OpenBIM Components, ou intégration Autodesk Forge).
    *   [ ] Créer un composant Filament pour afficher le viewer 3D dans la vue détail d'un Chantier.
    *   [ ] Gérer le chargement asynchrone des fichiers modèles lourds.
    *   [ ] Permettre la navigation basique (orbite, zoom, pan) et la sélection d'objets.

## Priorité 2 : Amélioration du Module Core / SaaS

**Objectif** : Renforcer la gestion des abonnements et la flexibilité des offres.

*   **Tâches** :
    *   [ ] Implémenter la gestion des limites par Plan (Quotas).
        *   Ex: Limiter le nombre d'utilisateurs actifs, le stockage GED, le nombre de chantiers actifs.
    *   [ ] Créer un Middleware ou une Policy pour vérifier les quotas avant chaque création de ressource critique.
    *   [ ] Ajouter une interface pour visualiser l'utilisation des quotas par entreprise.

## Priorité 3 : Optimisation du Module Chantiers (Planification)

**Objectif** : Offrir une vue visuelle de la planification des chantiers.

*   **Tâches** :
    *   [ ] Intégrer une vue Gantt (ex: via une librairie JS comme dhtmlxGantt ou un plugin Filament existant).
    *   [ ] Permettre la visualisation des dates de début et de fin des chantiers.
    *   [ ] Afficher les ressources allouées (Chefs de chantier, Équipes) sur le planning.

## Priorité 4 : Renforcement des Tests Automatisés

**Objectif** : Sécuriser les modules critiques (Compta, Paie) avant la mise en production à grande échelle.

*   **Tâches** :
    *   [ ] Écrire des tests unitaires pour le `PayrollCalculator` (scénarios complexes d'heures sup, absences, primes).
    *   [ ] Écrire des tests d'intégration pour la génération du FEC (vérification de la séquence, de l'équilibre).
    *   [ ] Mettre en place des tests de bout en bout (E2E) pour le flux de validation des Notes de Frais.

## Priorité 5 : API Publique (Préparation)

**Objectif** : Préparer le terrain pour les intégrations tierces et les applications mobiles futures.

*   **Tâches** :
    *   [ ] Définir les endpoints API essentiels (Authentification, Récupération des Chantiers, Saisie des Heures).
    *   [ ] Configurer Laravel Sanctum pour l'authentification API par token.
    *   [ ] Générer une documentation API initiale (Swagger/OpenAPI).
