# Batistack - Documentation Technique des Modules

Ce document détaille l'implémentation technique et les mécanismes internes de chaque module du projet Batistack. Il est destiné aux développeurs pour comprendre l'architecture, les automatisations et les flux de données.

---

### Module : Core / SaaS

- **Description Fonctionnelle** : Gestion des entreprises (Tenants), des abonnements (Plans) et des fonctionnalités activables.
- **Implémentation Technique** :
    - **Modèles** :
        - `app/Models/Core/Company.php` : Représente un tenant.
        - `app/Models/Core/Feature.php` : Représente une fonctionnalité activable (Module, Option, Service).
        - `app/Models/Core/Plan.php` : Représente un niveau d'abonnement.
    - **Enums** :
        - `app/Enums/Core/TypeFeature.php` : Définit les types de fonctionnalités (`MODULE`, `OPTION`, `SERVICE`).
        - `app/Enums/UserRole.php` : Définit les rôles utilisateurs (`SUPERADMIN`, `ADMINISTRATEUR`, `CLIENT`, `FOURNISSEUR`, `SALARIE`, `COMPTABILITE`).
    - **Administration** :
        - `app/Filament/Resources/Core/Features/FeatureResource.php` : Gestion CRUD des fonctionnalités.
        - `app/Filament/Resources/Core/Companies/CompanyResource.php` : Gestion CRUD des entreprises.

---

### Module : Pointage / RH

- **Description Fonctionnelle** : Saisie des heures des employés et calcul du coût de la main-d'œuvre pour les chantiers.
- **Implémentation Technique** :
    - **Modèle Principal** : `app/Models/RH/Timesheet.php`. Ce modèle est central et contient la logique de calcul du coût (`cost`) d'une session de travail.
    - **Types d'Heures** : Utilise l'Enum `app/Enums/RH/TimesheetType.php` pour catégoriser les types de pointage (Travail, Trajet, Absence, Formation, **Heures Supplémentaires 25% et 50%, Heures de Nuit, Heures du Dimanche**).
    - **Automatisation (Pointages Manquants)** : Une tâche planifiée (cron) exécute la commande `app/Console/Commands/CheckMissingTimesheetsCommand.php`. Cette commande identifie les employés n'ayant pas rempli leurs heures et leur envoie une notification via `app/Notifications/MissingTimesheetNotification.php`.

---

### Module : Chantiers

- **Description Fonctionnelle** : Gestion des projets, de leurs coûts et de leur localisation.
- **Implémentation Technique** :
    - **Automatisation (Calcul des Coûts)** : Le coût total de main-d'œuvre d'un chantier est mis à jour automatiquement. L'observer `app/Observers/RH/TimesheetObserver.php` écoute les événements `created`, `updated`, `deleted` du modèle `Timesheet`. À chaque événement, il déclenche le recalcul du coût total sur le modèle `Chantier` associé, **en prenant en compte les majorations pour les heures supplémentaires, de nuit et du dimanche**.
    - **Géocodage** : Une automatisation (probablement un observer sur le modèle `Chantier`) convertit l'adresse d'un chantier en coordonnées GPS lors de sa création ou modification.
    - **Coûts de Location** : Le modèle `Chantiers` inclut `total_rental_cost`, mis à jour automatiquement par l'observer `app/Observers/Locations/RentalContractObserver.php` lors des modifications des contrats de location liés.
    - **Coûts de Flotte (TCO)** : Le modèle `Chantiers` inclut `total_fleet_cost`. Le Job `app/Jobs/Fleets/AllocateFleetCostsJob.php` (planifié quotidiennement) impute le coût journalier des véhicules (`internal_daily_cost`) aux chantiers en croisant les assignations de flotte et les pointages des employés.
    - **Suivi Budgétaire et Rentabilité** :
        - Le modèle `Chantiers` inclut des champs pour les coûts réels (`total_labor_cost`, `total_material_cost`, `total_rental_cost`, `total_purchase_cost`, `total_fleet_cost`) et budgétés (`budgeted_revenue`, `budgeted_labor_cost`, `budgeted_material_cost`, `budgeted_rental_cost`, `budgeted_purchase_cost`, `budgeted_fleet_cost`).
        - Des accesseurs (`getTotalRealCostAttribute`, `getRealMarginAttribute`, etc.) sont disponibles pour calculer en temps réel la marge et l'écart par rapport au budget.
        - La commande `app/Console/Commands/Chantiers/GenerateProfitabilityReportCommand.php` génère des rapports de rentabilité en PDF et CSV pour un ou plusieurs chantiers.
        - **Historisation** : Le modèle `app/Models/Chantiers/ChantierReport.php` stocke une référence à chaque rapport généré, assurant la traçabilité.

---

### Module : Notes de Frais (NDF)

- **Description Fonctionnelle** : Soumission, validation et traitement comptable des dépenses des employés.
- **Implémentation Technique** :
    - **Workflow de Validation** : Le processus est géré par des statuts définis dans l'Enum `app/Enums/NoteFrais/ExpenseStatus.php`.
    - **Automatisation (Comptabilisation)** :
        1.  L'observer `app/Observers/NoteFrais/ExpenseObserver.php` surveille les changements sur le modèle `Expense`.
        2.  Lorsque le statut passe à `Validated`, l'observer instancie et appelle la méthode `postExpenseEntry()` du service `app/Services/Comptabilite/ExpenseComptaService.php`.
        3.  Ce service crée les écritures de débit (charge, TVA) et de crédit (compte de l'employé/fournisseur) dans la table `compta_entries`. Il gère également la recherche/création du `Tiers` associé à l'employé et l'assigne à l'écriture comptable (`tier_id`).
        4.  Une fois les écritures créées, le service met à jour le statut de l'`Expense` à `Posted`.
    - **Suivi de Remboursement** : Le modèle `Expense` inclut les champs `reimbursed_at` et `reimbursed_by_payroll_slip_id` pour tracer le remboursement via la paie.

---

### Module : Commerce / Facturation (Ventes & Achats)

- **Description Fonctionnelle** : Gestion des documents de vente (devis, factures) et des documents d'achat (factures fournisseurs).
- **Implémentation Technique** :
    - **Documents de Vente (`SalesDocument`)** :
        - **Modèle Principal** : `app/Models/Facturation/SalesDocument.php`.
        - **Statuts** : `app/Enums/Facturation/SalesDocumentStatus.php`.
        - **Automatisation (Comptabilisation)** :
            1.  L'observer `app/Observers/Facturation/SalesDocumentObserver.php` surveille les changements de statut.
            2.  Lorsque le statut d'une facture (`SalesDocumentType::Invoice`) passe à `Sent` ou `Paid`, il déclenche le service `app/Services/Comptabilite/SalesDocumentComptaService.php`.
            3.  Ce service crée les écritures comptables correspondantes (débit client, crédit vente, crédit TVA collectée) dans `compta_entries`.
    - **Documents d'Achat (`PurchaseDocument`)** :
        - **Modèle Principal** : `app/Models/Facturation/PurchaseDocument.php`. Gère les factures reçues des fournisseurs.
        - **Statuts** : `app/Enums/Facturation/PurchaseDocumentStatus.php`.
        - **Automatisation (Comptabilisation)** :
            1.  L'observer `app/Observers/Facturation/PurchaseDocumentObserver.php` surveille les changements de statut.
            2.  Lorsque le statut d'une facture fournisseur passe à `Approved` ou `Paid`, il déclenche le service `app/Services/Comptabilite/PurchaseDocumentComptaService.php`.
            3.  Ce service crée les écritures comptables correspondantes (débit charge, débit TVA déductible, crédit fournisseur) dans `compta_entries`.

---

### Module : Banque & Paiements

- **Description Fonctionnelle** : Gestion des comptes bancaires, synchronisation des transactions et rapprochement.
- **Implémentation Technique** :
    - **Synchronisation Externe** : Le Job `app/Jobs/Banque/SyncBridgeTransactionJob.php` utilise un service (ex: `app/Services/Bridge/BridgeService.php`) pour communiquer avec l'API BridgeAPI et importer les transactions bancaires dans `app/Models/Banque/BankTransaction.php`.
    - **Rapprochement Automatique** : Le Job `app/Jobs/Banque/AutoReconcileTransactionJob.php` est déclenché après l'importation pour rapprocher les transactions bancaires avec les paiements internes.
    - **Automatisation (Comptabilisation)** :
        1.  L'observer `app/Observers/Banque/BankTransactionObserver.php` surveille la création de nouvelles transactions importées.
        2.  Il déclenche le service `app/Services/Comptabilite/BankTransactionComptaService.php`.
        3.  Ce service crée les écritures comptables pour la transaction bancaire (mouvement sur le compte bancaire et contrepartie sur un compte d'attente) dans `compta_entries`.
    - **Mise à jour des Soldes** : Un observer sur le modèle `Payment` met à jour le solde du `BankAccount` associé chaque fois qu'un paiement est marqué comme `cleared` (compensé) ou si un paiement est supprimé.

---

### Module : Comptabilité

- **Description Fonctionnelle** : Centralisation des écritures comptables, génération des journaux, exports légaux (FEC) et rapports comptables.
- **Implémentation Technique** :
    - **Modèle Central** : `app/Models/Comptabilite/ComptaEntry.php` est le modèle qui stocke chaque ligne d'écriture comptable. Il utilise une relation polymorphique (`sourceable`) pour lier une écriture à sa source (ex: une `Expense`, une `SalesDocument`, une `PurchaseDocument`, une `BankTransaction`, un `RentalContract`, une `Intervention`). Il inclut un `tier_id` pour l'association directe avec un `Tiers` et un `piece_reference` pour la traçabilité.
    - **Services de Comptabilisation** : La logique est déportée dans des services spécialisés :
        - `app/Services/Comptabilite/ExpenseComptaService.php` : Gère la comptabilisation des notes de frais.
        - `app/Services/Comptabilite/UlysComptaService.php` : Gère la comptabilisation des dépenses de flotte Ulys.
        - `app/Services/Comptabilite/SalesDocumentComptaService.php` : Gère la comptabilisation des documents de vente.
        - `app/Services/Comptabilite/PurchaseDocumentComptaService.php` : Gère la comptabilisation des documents d'achat.
        - `app/Services/Comptabilite/BankTransactionComptaService.php` : Gère la comptabilisation des transactions bancaires.
        - `app/Services/Comptabilite/RentalContractComptaService.php` : Gère la comptabilisation des contrats de location (Factures fournisseurs).
        - `app/Services/Comptabilite/InterventionComptaService.php` : Gère la comptabilisation des coûts des interventions (Analytique).
    - **Export FEC** :
        - Le Job `app/Jobs/Comptabilite/GenerateFecJob.php` est responsable de la génération du Fichier des Écritures Comptables (FEC).
        - Il utilise les relations `journal`, `account` et `tier` pour extraire les données.
        - Il remplit les champs `CompAuxNum` et `CompAuxLib` avec les informations du `Tiers` associé à l'écriture.
        - **Numérotation Séquentielle** : Le job implémente une logique stricte pour garantir que le champ `EcritureNum` est séquentiel et ininterrompu par journal et par exercice, en utilisant un compteur en mémoire et un tri précis (`journal_id`, `date`, `id`).
    - **Reporting Comptable** :
        - Le service `app/Services/Comptabilite/ComptaReportingService.php` fournit des méthodes pour récupérer les écritures par journal (`getJournalEntries`) ou par compte (`getGeneralLedgerEntries`), et calculer les soldes (`getAccountBalanceAtDate`).
        - **Génération de Rapports Automatisée** : La commande `app/Console/Commands/Comptabilite/GenerateAccountingReportsCommand.php` génère :
            - Un fichier CSV par journal.
            - **Un fichier CSV consolidé pour le Grand Livre (tous comptes)**.
            - Supporte le multi-tenant (boucle sur toutes les compagnies).

---

### Module : Flottes

- **Description Fonctionnelle** : Gestion de la flotte de véhicules, des assurances, des consommations, des maintenances et des assignations.
- **Implémentation Technique** :
    - **Modèle Principal** : `app/Models/Fleets/Fleet.php` est enrichi avec des détails comme `name`, `registration_number`, `type` (via `app/Enums/Fleets/FleetType.php`), `brand`, `model`, `vin`, `mileage`, et `internal_daily_cost`.
    - **Synchronisation Ulys** : La commande `app/Console/Commands/Fleets/SyncUlysConsumptionsCommand.php` utilise le service `app/Services/Fleets/UlysService.php` pour récupérer les données de consommation de l'API Ulys et les stocker dans le modèle `app/Models/Fleets/UlysConsumption.php`. Le modèle `UlysConsumption` inclut un `status` (`Pending`, `Posted`) pour le suivi comptable.
    - **Gestion des Assurances** :
        - Le modèle `app/Models/Fleets/Insurance.php` est enrichi avec `contract_number`, `insurer_name`, `is_active`, et `notified_at`.
        - La commande `app/Console/Commands/Fleets/CheckFleetExpirationsCommand.php` vérifie les assurances expirant bientôt.
        - Elle envoie des notifications via `app/Notifications/Fleets/InsuranceExpiringNotification.php` aux utilisateurs concernés.
    - **Gestion des Maintenances** :
        - Le modèle `app/Models/Fleets/Maintenance.php` est enrichi avec `type` (via `app/Enums/Fleets/MaintenanceType.php`), `description`, `provider_name`, `mileage_at_maintenance`, `next_mileage`, et `notified_at`.
        - La commande `app/Console/Commands/Fleets/CheckMaintenanceAlertsCommand.php` vérifie les maintenances à venir (par date ou kilométrage).
        - Elle envoie des notifications via `app/Notifications/Fleets/MaintenanceAlertNotification.php` aux utilisateurs concernés.
    - **Assignation des Véhicules** :
        - **Modèle Principal** : `app/Models/Fleets/FleetAssignment.php` gère l'historique des assignations, incluant `start_date`, `end_date`, `status` (via `app/Enums/Fleets/FleetAssignmentStatus.php`) et `notified_at`.
        - **Statuts** : `app/Enums/Fleets/FleetAssignmentStatus.php` pour suivre l'état de l'assignation (Planifiée, Active, Terminée, Annulée).
        - **Sécurité (Anti-Conflit)** : L'observer `app/Observers/Fleets/FleetAssignmentObserver.php` implémente une vérification stricte (`checkForConflicts`) lors de la création/mise à jour pour empêcher l'assignation simultanée d'un véhicule à plusieurs entités.
        - **Automatisation (Mise à jour du statut)** : L'observer `app/Observers/Fleets/FleetAssignmentObserver.php` met à jour automatiquement le `status` de l'assignation en fonction des dates de début et de fin.
        - **Automatisation (Notifications)** :
            1.  L'observer `app/Observers/Fleets/FleetAssignmentObserver.php` envoie des notifications (`app/Notifications/Fleets/FleetAssignedNotification.php`) lors de la création, mise à jour ou suppression d'une assignation.
            2.  La commande `app/Console/Commands/Fleets/CheckFleetAssignmentRemindersCommand.php` vérifie quotidiennement les assignations dont la fin approche et envoie des rappels (`app/Notifications/Fleets/FleetAssignmentReminderNotification.php`) aux entités assignées. Cette commande est planifiée via `routes/console.php`.
        - Les modèles `app/Models/Fleets/Fleet`, `app/Models/RH/Employee` et `app/Models/RH/Team` ont des relations polymorphiques (`MorphMany`, `MorphToMany`) pour gérer ces assignations.
    - **Imputation des Coûts** : Le Job `app/Jobs/Fleets/AllocateFleetCostsJob.php` impute quotidiennement le `internal_daily_cost` des véhicules aux chantiers correspondants.

---

### Module : Paie

- **Description Fonctionnelle** : Préparation, calcul et export des fiches de paie.
- **Implémentation Technique** :
    - **Service de Calcul** : Le service `app/Services/Paie/PayrollCalculator.php` est le moteur principal. Il collecte les heures (`Timesheet`) et les frais (`Expense`) pour une période donnée, les agrège et crée des `PayrollVariable` pour un `PayrollSlip`. **Il inclut désormais la gestion des différents types d'heures majorées (supplémentaires, nuit, dimanche) pour le calcul.**
    - **Variables de Paie** : L'Enum `app/Enums/Paie/PayrollVariableType.php` est utilisé pour standardiser les différents types d'éléments de paie (incluant désormais `MealVoucher` et `Transport`).
    - **Structure** : Les modèles `PayrollPeriods`, `PayrollSlip`, et `PayrollVariable` forment la structure de base pour stocker les données de paie. Le modèle `PayrollSlip` implémente `HasMedia` et inclut un champ `processed_at`.
    - **Export de Paie** :
        - Le service `app/Services/Paie/PayrollExportService.php` génère un fichier CSV à partir des `PayrollVariable` d'un `PayrollSlip`.
        - **Configuration Flexible** : Utilise `config/payroll.php` pour définir les formats d'export.
        - **Configuration par Compagnie** : Le modèle `Company` inclut `payroll_export_format` et `payroll_external_reference_id` pour personnaliser l'export par client.
        - **Mapping des Codes** : Supporte un mapping configurable (`code_mapping`) pour traduire les types internes (`PayrollVariableType`) vers les codes spécifiques des logiciels de paie (Silae, Sage).
        - Le Job `app/Jobs/Paie/GeneratePayrollExportJob.php` orchestre le calcul via `PayrollCalculator` et l'export via `PayrollExportService`, puis attache le CSV au `PayrollSlip` via Spatie Media Library. **Il marque également les notes de frais comme remboursées après le traitement du bulletin de paie.**

---

### Module : GPAO

- **Description Fonctionnelle** : Gestion des Ordres de Fabrication (OF), de la nomenclature (recettes) et des besoins en matériaux.
- **Implémentation Technique** :
    - **Nomenclature** : La gestion des "recettes" est gérée par le modèle `app/Models/Articles/ProductAssembly.php`.
    - **Ordres de Fabrication** :
        - **Modèle Principal** : `app/Models/GPAO/ProductionOrder.php` pour gérer les ordres de fabrication, **incluant les champs de planification (`assigned_to`, `planned_start_date`, `planned_end_date`) et de suivi (`actual_start_date`, `actual_end_date`)**.
        - **Statuts** : `app/Enums/GPAO/ProductionOrderStatus.php` pour suivre l'état de l'OF (Brouillon, Planifié, En cours, Terminé, Annulé).
        - **Automatisation (Création à partir des commandes)** : L'observer `app/Observers/Facturation/SalesDocumentObserver.php` crée automatiquement un OF si le stock est insuffisant lors de la validation d'un devis.
        - **Automatisation (Mise à jour des stocks)** : L'observer `app/Observers/GPAO/ProductionOrderObserver.php` met à jour les stocks (décrémentation des composants, incrémentation du produit fini) lorsque l'OF passe au statut `Completed`.
        - **Automatisation (Notifications)** : L'observer `app/Observers/GPAO/ProductionOrderObserver.php` envoie des notifications (`app/Notifications/GPAO/ProductionOrderNotification.php`) lors de la création, mise à jour (changement de statut ou d'assignation) et suppression d'un OF.
        - **Automatisation (Alertes de retard)** : La commande `app/Console/Commands/GPAO/CheckProductionOrderDelaysCommand.php` vérifie quotidiennement les OF en retard et envoie des alertes. Cette commande est planifiée via `routes/console.php`.
        - **Calcul du Coût de Main-d'Œuvre** : L'observer `app/Observers/RH/TimesheetObserver.php` recalcule le `total_labor_cost` de l'OF à chaque modification d'un pointage lié.
        - **Calcul du Coût des Matériaux** : L'observer `app/Observers/GPAO/ProductionOrderObserver.php` calcule le `total_material_cost` de l'OF lorsque celui-ci est terminé.
    - **Calcul des Besoins et Suggestions d'Achat (MRP Simplifié)** :
        - Le service `app/Services/GPAO/MaterialRequirementService.php` calcule les déficits en matériaux en comparant les besoins des OF (`Planned` et `InProgress`) avec le stock disponible.
        - La commande `app/Console/Commands/GPAO/GeneratePurchaseSuggestionsCommand.php` utilise ce service pour générer automatiquement des brouillons de commandes fournisseurs (`PurchaseDocument`) pour les produits manquants, en se basant sur le fournisseur principal (`main_supplier_id`) du produit.

---

### Module : Locations

- **Description Fonctionnelle** : Gestion des contrats de location de matériel.
- **Implémentation Technique** :
    - **Structure** :
        - **Modèles** : `app/Models/Locations/RentalContract.php` (avec `periodicity`, `deposit_amount`, `next_billing_date`) et `app/Models/Locations/RentalContractLine.php`.
        - **Statuts** : `app/Enums/Locations/RentalContractStatus.php`.
    - **Automatisation (Calcul des Totaux)** : L'observer `app/Observers/Locations/RentalContractLineObserver.php` recalcule les totaux du contrat à chaque modification d'une ligne.
    - **Automatisation (Comptabilisation)** : L'observer `app/Observers/Locations/RentalContractObserver.php` déclenche le service `app/Services/Comptabilite/RentalContractComptaService.php` lorsque le statut du contrat passe à `Completed`. Ce service génère un `PurchaseDocument` (Facture Fournisseur).
    - **Automatisation (Facturation Fournisseur)** : La commande `app/Console/Commands/Locations/GenerateRentalSupplierInvoicesCommand.php` génère automatiquement les factures fournisseurs (`PurchaseDocument`) pour les contrats actifs selon leur périodicité.
    - **Intégration Coûts Chantiers** : L'observer `app/Observers/Locations/RentalContractObserver.php` met à jour le `total_rental_cost` sur le `Chantier` associé lors des modifications du contrat.
    - **Alertes** : La commande `app/Console/Commands/Locations/CheckRentalExpirationsCommand.php` notifie les admins des contrats arrivant à échéance.

---

### Module : Interventions

- **Description Fonctionnelle** : Gestion et suivi des interventions de maintenance sur les sites ou chantiers.
- **Implémentation Technique** :
    - **Structure** :
        - **Modèles** : `app/Models/Interventions/Intervention.php` (avec `billing_type`, `target_margin_rate`, `actual_margin`) et `app/Models/Interventions/InterventionProduct.php` (pivot).
        - **Statuts** : `app/Enums/Interventions/InterventionStatus.php`.
    - **Automatisation (Calcul des Coûts)** :
        - **Main-d'œuvre** : L'observer `app/Observers/RH/TimesheetObserver.php` recalcule le `total_labor_cost` de l'intervention à chaque modification d'un pointage lié.
        - **Matériaux** : L'observer `app/Observers/Interventions/InterventionProductObserver.php` recalcule le `total_material_cost` et met à jour les stocks à chaque modification des pièces utilisées.
        - **Déstockage Intelligent** : `InterventionProductObserver` cherche le stock dans le dépôt par défaut de l'entreprise (`Warehouse::where('is_default', true)`).
    - **Automatisation (Notifications)** : L'observer `app/Observers/Interventions/InterventionObserver.php` envoie des notifications (`app/Notifications/Interventions/InterventionNotification.php`) lors de la création ou du changement de statut.
    - **Automatisation (Comptabilisation)** : L'observer `app/Observers/Interventions/InterventionObserver.php` déclenche le service `app/Services/Comptabilite/InterventionComptaService.php` lorsque le statut passe à `Completed`. Ce service génère des écritures analytiques (Coûts MO/Matériaux) avec lien vers le Tiers et référence de pièce.
    - **Automatisation (Facturation)** : L'observer `app/Observers/Interventions/InterventionObserver.php` déclenche la méthode `generateSalesDocument()` du modèle `Intervention` lorsque le statut passe à `Completed` et que l'intervention est facturable. Cette méthode gère la facturation au forfait ou en régie, en appliquant la marge cible.

---

### Module : 3D Vision

- **Description Fonctionnelle** : Visualisation 3D des projets.
- **Implémentation Technique** :
    - **Structure** : Le modèle `app/Models/Chantiers/ProjectModel.php` permet de lier des maquettes numériques (IFC, GLB...) à un chantier. Il inclut des champs pour le calage manuel (`model_origin_latitude`, `rotation_z`, etc.) et utilise Spatie Media Library pour le stockage des fichiers.
    - **Intégration Viewer BIM/IFC** : L'intégration d'un visualiseur est à faire.

---

### Module : Pilotage / Reporting

- **Description Fonctionnelle** : Centralisation des calculs de KPI pour les tableaux de bord.
- **Implémentation Technique** :
    - **Service Principal** : `app/Services/Reporting/DashboardService.php`.
    - **Méthodes Clés** :
        - `getChantiersRentability()`: Calcule le top/flop des chantiers par marge.
        - `getFinancialAlerts()`: Agrège les créances en retard et les dettes à venir.
        - `getFleetUtilization()`: Calcule le taux d'utilisation de la flotte sur 30 jours.

---

### RÉFÉRENCES CODE CLÉS (Pour le Contexte)

| Catégorie | Fichier | Rôle / Utilisation |
|---|---|---|
| Core/SaaS | app/Models/Core/Feature.php | Modèle des fonctionnalités activables (Module, Option, Service). |
| Core/SaaS | app/Enums/Core/TypeFeature.php | Enum des types de fonctionnalités. |
| Core/SaaS | app/Enums/UserRole.php | Enum des rôles utilisateurs (SuperAdmin, Admin, Salarié...). |
| RH/Pointage | app/Models/RH/Timesheet.php | Modèle central du pointage, calcule le cost du travail. |
| RH/Automation | app/Observers/RH/TimesheetObserver.php | Logique métier/règles, déclenche le recalcul du coût chantier après chaque modification d'heure. |
| Compta/NDF | app/Services/Comptabilite/ExpenseComptaService.php | Service de comptabilisation des notes de frais, inclut `tier_id`. |
| Compta/NDF | app/Observers/NoteFrais/ExpenseObserver.php | Déclenche la comptabilisation des NDF après validation. |
| Compta/Ulys | app/Services/Comptabilite/UlysComptaService.php | Service de comptabilisation des consommations Ulys, inclut `tier_id`. |
| Compta/FEC | app/Jobs/Comptabilite/GenerateFecJob.php | Génération du Fichier des Écritures Comptables (FEC). |
| Compta/Base | app/Models/Comptabilite/ComptaEntry.php | Modèle d'écriture comptable, inclut `tier_id` et relation `tier`. |
| Compta/Reporting | app/Services/Comptabilite/ComptaReportingService.php | Service de récupération des données pour les journaux et le Grand Livre. |
| Compta/Rapports | app/Console/Commands/Comptabilite/GenerateAccountingReportsCommand.php | Commande Artisan pour générer les rapports comptables (journaux, grand livre) en CSV. Planifiée via `routes/console.php`. |
| Chantiers/Reporting | app/Console/Commands/Chantiers/GenerateProfitabilityReportCommand.php | Commande Artisan pour générer les rapports de rentabilité (PDF, CSV) par chantier. |
| Facturation/Vente | app/Models/Facturation/SalesDocument.php | Modèle principal des documents de vente. |
| Facturation/Vente | app/Enums/Facturation/SalesDocumentStatus.php | Enum des statuts des documents de vente. |
| Facturation/Vente | app/Services/Comptabilite/SalesDocumentComptaService.php | Service de comptabilisation des documents de vente. |
| Facturation/Vente | app/Observers/Facturation/SalesDocumentObserver.php | Déclenche la comptabilisation des documents de vente. |
| Facturation/Achat | database/migrations/2024_01_01_000000_create_purchase_documents_table.php | Migration pour la table des documents d'achat. |
| Facturation/Achat | app/Models/Facturation/PurchaseDocument.php | Modèle principal des documents d'achat (factures fournisseurs). |
| Facturation/Achat | app/Enums/Facturation/PurchaseDocumentStatus.php | Enum des statuts des documents d'achat. |
| Facturation/Achat | app/Services/Comptabilite/PurchaseDocumentComptaService.php | Service de comptabilisation des documents d'achat. |
| Facturation/Achat | app/Observers/Facturation/PurchaseDocumentObserver.php | Déclenche la comptabilisation des documents d'achat. |
| Banque/Transactions | app/Models/Banque/BankTransaction.php | Modèle des transactions bancaires importées. |
| Banque/Transactions | app/Services/Comptabilite/BankTransactionComptaService.php | Service de comptabilisation des transactions bancaires. |
| Banque/Transactions | app/Observers/Banque/BankTransactionObserver.php | Déclenche la comptabilisation des transactions bancaires. |
| RH/Paie | app/Enums/Paie/PayrollVariableType.php | Enum des variables de paie (Heures, Primes, Frais). |
| Paie/Calcul | app/Services/Paie/PayrollCalculator.php | Service de calcul des fiches de paie (agrégation heures/frais). |
| Paie/Export | app/Services/Paie/PayrollExportService.php | Service de génération du fichier CSV d'export de paie, avec logique Silae/Sage affinée. |
| Paie/Export | app/Enums/Paie/PayrollExportFormat.php | Enum des formats d'export de paie (Silae, Sage, GenericCSV). |
| Paie/Job | app/Jobs/Paie/GeneratePayrollExportJob.php | Orchestre le calcul et l'export de paie, attache le CSV au `PayrollSlip`. |
| Paie/Structure | database/migrations/2025_12_10...create_payroll_slips_table.php | Structure de la fiche de paie. |
| Paie/Structure | database/migrations/2025_12_12...add_processed_at_to_payroll_slips_table.php | Ajout du champ `processed_at` au `PayrollSlip`. |
| Flottes/Base | app/Models/Fleets/Fleet.php | Modèle principal de la flotte, enrichi avec détails et relations d'assignation. |
| Flottes/Types | app/Enums/Fleets/FleetType.php | Enum des types de véhicules de la flotte. |
| Flottes/Ulys | app/Console/Commands/Fleets/SyncUlysConsumptionsCommand.php | Synchronisation des consommations Ulys. |
| Flottes/Ulys | app/Models/Fleets/UlysConsumption.php | Modèle pour les consommations Ulys, inclut `status`. |
| Flottes/Ulys | app/Services/Fleets/UlysService.php | Service d'intégration avec l'API Ulys. |
| Flottes/Assurance | app/Models/Fleets/Insurance.php | Modèle d'assurance, enrichi avec détails et `notified_at`. |
| Flottes/Alerte | app/Console/Commands/Fleets/CheckFleetExpirationsCommand.php | Commande de vérification des expirations d'assurance. |
| Flottes/Alerte | app/Notifications/Fleets/InsuranceExpiringNotification.php | Notification d'expiration d'assurance. |
| Flottes/Maintenance | app/Models/Fleets/Maintenance.php | Modèle de maintenance, enrichi avec détails et `notified_at`. |
| Flottes/Maintenance | app/Enums/Fleets/MaintenanceType.php | Enum des types de maintenance. |
| Flottes/Maintenance | app/Console/Commands/Fleets/CheckMaintenanceAlertsCommand.php | Commande de vérification des maintenances à venir. |
| Flottes/Maintenance | app/Notifications/Fleets/MaintenanceAlertNotification.php | Notification d'alerte de maintenance. |
| Flottes/Assignation | app/Models/Fleets/FleetAssignment.php | Modèle pour l'assignation polymorphique des véhicules, incluant `status` et `notified_at`. |
| Flottes/Assignation | app/Enums/Fleets/FleetAssignmentStatus.php | Enum des statuts d'assignation de flotte. |
| Flottes/Assignation | database/migrations/2025_12_12_170000_create_fleet_assignments_table.php | Migration pour la table d'assignation des flottes. |
| Flottes/Assignation | database/migrations/2025_12_12_170001_add_status_and_notified_at_to_fleet_assignments_table.php | Migration pour ajouter les champs `status` et `notified_at` aux assignations de flotte. |
| Flottes/Assignation | app/Observers/Fleets/FleetAssignmentObserver.php | Gère la mise à jour du statut et l'envoi de notifications pour les assignations de flotte. |
| Flottes/Assignation | app/Notifications/Fleets/FleetAssignedNotification.php | Notification d'assignation de flotte (création, mise à jour, suppression). |
| Flottes/Assignation | app/Notifications/Fleets/FleetAssignmentReminderNotification.php | Notification de rappel de fin d'assignation de flotte. |
| Flottes/Assignation | app/Console/Commands/Fleets/CheckFleetAssignmentRemindersCommand.php | Commande de vérification et d'envoi des rappels de fin d'assignation de flotte. Planifiée via `routes/console.php`. |
| Flottes/Structure | database/migrations/2025_12_11...create_maintenances_table.php | Stocke les informations de suivi et coût des entretiens. |
| Flottes/Imputation | app/Jobs/Fleets/AllocateFleetCostsJob.php | Job d'imputation des coûts journaliers des véhicules aux chantiers. |
| NDF/Structure | database/migrations/2025_12_12...add_reimbursement_fields_to_expenses_table.php | Ajout des champs de remboursement aux notes de frais. |
| Compta/Structure | database/migrations/2025_12_12...add_tier_id_to_compta_entries_table.php | Ajout du champ `tier_id` aux écritures comptables. |
| Core/Scheduling | routes/console.php | Fichier de planification des commandes Artisan (Laravel 12+). |
| GPAO/OF | database/migrations/2024_01_01_000000_create_production_orders_table.php | Migration pour la table des ordres de fabrication. |
| GPAO/OF | database/migrations/2024_01_01_000000_add_planning_fields_to_production_orders_table.php | Migration pour ajouter les champs de planification aux ordres de fabrication. |
| GPAO/OF | app/Models/GPAO/ProductionOrder.php | Modèle principal des ordres de fabrication, incluant les champs de planification (`assigned_to`, `planned_start_date`, `planned_end_date`). |
| GPAO/OF | app/Enums/GPAO/ProductionOrderStatus.php | Enum des statuts des ordres de fabrication. |
| GPAO/OF | app/Observers/GPAO/ProductionOrderObserver.php | Gère la mise à jour des stocks et l'envoi de notifications lors des changements de statut d'un ordre de fabrication. |
| GPAO/OF | app/Notifications/GPAO/ProductionOrderNotification.php | Notification pour les ordres de fabrication (création, mise à jour, changement de statut). |
| GPAO/OF | database/migrations/2024_01_01_000001_add_actual_dates_to_production_orders_table.php | Migration pour ajouter les dates réelles aux ordres de fabrication. |
| GPAO/OF | app/Console/Commands/GPAO/CheckProductionOrderDelaysCommand.php | Commande de vérification et d'envoi des alertes de retard pour les ordres de fabrication. Planifiée via `routes/console.php`. |
| GPAO/OF | database/migrations/2024_01_01_000002_add_production_order_id_to_timesheets_table.php | Migration pour lier les pointages aux ordres de fabrication. |
| GPAO/OF | database/migrations/2024_01_01_000003_add_total_labor_cost_to_production_orders_table.php | Migration pour ajouter le coût de la main-d'œuvre aux ordres de fabrication. |
| GPAO/OF | database/migrations/2024_01_01_000004_add_sales_document_line_id_to_production_orders_table.php | Migration pour lier les ordres de fabrication aux lignes de commande client. |
| GPAO/MRP | app/Services/GPAO/MaterialRequirementService.php | Service de calcul des besoins en matériaux (MRP simplifié). |
| GPAO/MRP | app/Console/Commands/GPAO/GeneratePurchaseSuggestionsCommand.php | Commande de génération des suggestions d'achats. |
| Locations/Base | app/Models/Locations/RentalContract.php | Modèle principal des contrats de location. |
| Locations/Base | app/Models/Locations/RentalContractLine.php | Modèle pour les lignes de contrat de location. |
| Locations/Automation | app/Observers/Locations/RentalContractLineObserver.php | Recalcule les totaux du contrat à chaque modification d'une ligne. |
| Locations/Automation | app/Observers/Locations/RentalContractObserver.php | Déclenche la comptabilisation du contrat. |
| Locations/Compta | app/Services/Comptabilite/RentalContractComptaService.php | Service de comptabilisation des contrats de location. |
| Locations/Automation | app/Console/Commands/Locations/GenerateRentalSupplierInvoicesCommand.php | Commande de génération automatique des factures fournisseurs. |
| GPAO/OF | database/migrations/2025_12_12_270000_add_total_material_cost_to_production_orders_table.php | Migration pour ajouter le coût des matériaux aux ordres de fabrication. |
| Interventions/Base | app/Models/Interventions/Intervention.php | Modèle principal des interventions. |
| Interventions/Base | app/Models/Interventions/InterventionProduct.php | Modèle pivot pour les produits utilisés dans une intervention. |
| Interventions/Automation | app/Observers/Interventions/InterventionObserver.php | Déclenche les notifications et la comptabilisation. |
| Interventions/Automation | app/Observers/Interventions/InterventionProductObserver.php | Recalcule le coût des matériaux et met à jour les stocks. |
| Interventions/Compta | app/Services/Comptabilite/InterventionComptaService.php | Service de comptabilisation des coûts des interventions. |
| Interventions/Notifications | app/Notifications/Interventions/InterventionNotification.php | Notification pour les interventions. |
| Chantiers/Reporting | app/Models/Chantiers/ChantierReport.php | Modèle pour historiser les rapports de rentabilité. |
| Chantiers/Reporting | database/migrations/2025_12_12_330000_create_chantier_reports_table.php | Migration pour la table d'historisation des rapports. |
| Pilotage/Reporting | app/Services/Reporting/DashboardService.php | Service de centralisation des calculs de KPI. |
| 3D/Structure | app/Models/Chantiers/ProjectModel.php | Modèle pour lier les maquettes 3D aux chantiers. |
| 3D/Structure | database/migrations/2025_12_26_200000_create_project_models_table.php | Migration pour la table des maquettes 3D. |
