# Module GPAO - Note de Version

**Version :** 1.2
**Date :** 2025-01-02

---

## Fonctionnalités Clés

Le module GPAO (Gestion de la Production Assistée par Ordinateur) gère le cycle de fabrication des produits assemblés (ouvrages).

- **Ordres de Fabrication (OF)** : Création, planification et suivi des OF.
- **Génération Automatique d'OF** : Un OF est automatiquement créé à partir d'un devis accepté si le stock du produit fini est insuffisant.
- **Gestion des Stocks** :
    - Décrémentation automatique des composants lors de la clôture d'un OF.
    - Incrémentation automatique du stock du produit fini.
- **Calcul des Besoins (MRP Simplifié)** : Un service analyse les OF planifiés et génère des suggestions d'achats pour les composants manquants.
- **Suivi des Coûts de Production** :
    - Calcul du coût de la main-d'œuvre (via les pointages sur OF).
    - Calcul du coût des matériaux consommés.

## Dernières Améliorations (v1.2)

- **Planification Automatique (v1.1)** : Un service de planification a été intégré. Il calcule et assigne automatiquement des dates de début et de fin prévisionnelles aux OF, en se basant sur la charge de travail existante et la durée de fabrication des produits.
- **Contrôle Qualité (v1.2)** : Un système de points de contrôle qualité a été ajouté. Il est désormais possible de définir des checklists qualité par produit. La clôture d'un OF est bloquée si les contrôles obligatoires ne sont pas validés, garantissant ainsi la conformité de la production.

## Points Techniques & Dépendances

- **Modèles Principaux** :
    - `app/Models/GPAO/ProductionOrder.php`
    - `app/Models/GPAO/QualityCheckpoint.php` (Nouveau)
    - `app/Models/GPAO/QualityControl.php` (Nouveau)
- **Logique Métier & Automatisation** :
    - `app/Observers/GPAO/ProductionOrderObserver.php` (Amélioré pour la qualité).
    - `app/Services/GPAO/ProductionPlanningService.php` (Nouveau).
    - `app/Observers/Facturation/SalesDocumentObserver.php` (pour la création auto. des OF).
- **Dépendances Fortes** :
    - Module **Articles & Stock**.
    - Module **Pointage/RH**.
    - Module **Commerce / Facturation**.
