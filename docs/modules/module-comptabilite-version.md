# Module Comptabilité - Note de Version

**Version :** 1.1
**Date :** 2024-05-21

---

## Fonctionnalités Clés

Le module Comptabilité centralise toutes les opérations financières de l'entreprise, automatise la saisie et assure la conformité légale.

- **Comptabilisation Automatisée** : Les opérations suivantes sont automatiquement traduites en écritures comptables :
    - Factures de Vente
    - Factures d'Achat
    - Notes de Frais
    - Transactions Bancaires
    - Contrats de Location
    - Consommations de flotte (Ulys)
    - Coûts des Interventions
- **Gestion des Écritures** : Saisie manuelle et modification des écritures comptables.
- **Export Légal (FEC)** : Génération du Fichier des Écritures Comptables conforme aux normes de la DGFIP, avec une numérotation séquentielle stricte par journal.
- **Reporting Comptable** :
    - Génération des journaux comptables (Ventes, Achats, Banque, etc.) en format CSV.
    - Génération d'un Grand Livre consolidé.

## Dernières Améliorations (v1.1)

- **Validation Pré-Export FEC** : Avant chaque génération du FEC, un processus de validation automatique est lancé. Il vérifie :
    - L'équilibre global des écritures (Total Débit = Total Crédit).
    - L'intégrité des données (présence des comptes et journaux sur chaque écriture).
    - En cas d'erreur, l'export est stoppé et une notification détaillée est envoyée à l'utilisateur.

## Points Techniques & Dépendances

- **Modèle Principal** :
    - `app/Models/Comptabilite/ComptaEntry.php`
- **Logique Métier & Automatisation** :
    - **Services de comptabilisation** : `ExpenseComptaService`, `SalesDocumentComptaService`, `PurchaseDocumentComptaService`, etc.
    - **Observers** : Les observers sur les modèles (`Expense`, `SalesDocument`, etc.) déclenchent les services de comptabilisation.
    - `app/Jobs/Comptabilite/GenerateFecJob.php` (Amélioré).
- **Dépendances Fortes** :
    - Pratiquement tous les modules financiers (Facturation, Banque, NDF, Locations, Flottes).
