# Batistack - Documentation Technique des Modules

Ce document détaille l'implémentation technique et les mécanismes internes de chaque module du projet Batistack. Il est destiné aux développeurs pour comprendre l'architecture, les automatisations et les flux de données.

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
    - **Modèle Central** : `app/Models/Comptabilite/ComptaEntry.php` est le modèle qui stocke chaque ligne d'écriture comptable. Il utilise une relation polymorphique (`sourceable`) pour lier une écriture à sa source (ex: une `Expense`, une `SalesDocument`, une `PurchaseDocument`, une `BankTransaction`, un `RentalContract`). Il inclut un `tier_id` pour l'association directe avec un `Tiers`.
    - **Services de Comptabilisation** : La logique est déportée dans des services spécialisés :
        - `app/Services/Comptabilite/ExpenseComptaService.php` : Gère la comptabilisation des notes de frais.
        - `app/Services/Comptabilite/UlysComptaService.php` : Gère la comptabilisation des dépenses de flotte Ulys.
        - `app/Services/Comptabilite/SalesDocumentComptaService.php` : Gère la comptabilisation des documents de vente.
        - `app/Services/Comptabilite/PurchaseDocumentComptaService.php` : Gère la comptabilisation des documents d'achat.
        - `app/Services/Comptabilite/BankTransactionComptaService.php` : Gère la comptabilisation des transactions bancaires.
        - `app/Services/Comptabilite/RentalContractComptaService.php` : Gère la comptabilisation des contrats de location.
    - **Export FEC** :
        - Le Job `app/Jobs/Comptabilite/GenerateFecJob.php` est responsable de la génération du Fichier des Écritures Comptables (FEC).
        - Il utilise les relations `journal`, `account` et `tier` pour extraire les données.
        - Il remplit les champs `CompAuxNum` et `CompAuxLib` avec les informations du `Tiers` associé à l'écriture.
        - Il assure une numérotation séquentielle et ininterrompue du champ `EcritureNum` par journal et par mois, pour une conformité totale avec la norme DGFIP.
    - **Reporting Comptable** :
        - Le service `app/Services/Comptabilite/ComptaReportingService.php` fournit des méthodes pour récupérer les écritures par journal (`getJournalEntries`) ou par compte (`getGeneralLedgerEntries`), et calculer les soldes (`getAccountBalanceAtDate`).
        - **Génération de Rapports Automatisée** : La commande `app/Console/Commands/Comptabilite/GenerateAccountingReportsCommand.php` utilise ce service pour générer des fichiers CSV pour les journaux et le Grand Livre pour une période donnée. Cette commande est planifiée pour s'exécuter régulièrement (ex: mensuellement) via `routes/console.php`.

---

### Module : Flottes

- **Description Fonctionnelle** : Gestion de la flotte de véhicules, des assurances, des consommations, des maintenances et des assignations.
- **Implémentation Technique** :
    - **Modèle Principal** : `app/Models/Fleets/Fleet.php` est enrichi avec des détails comme `name`, `registration_number`, `type` (via `app/Enums/Fleets/FleetType.php`), `brand`, `model`, `vin`, `mileage`.
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
        - **Automatisation (Mise à jour du statut)** : L'observer `app/Observers/Fleets/FleetAssignmentObserver.php` met à jour automatiquement le `status` de l'assignation en fonction des dates de début et de fin.
        - **Automatisation (Notifications)** :
            1.  L'observer `app/Observers/Fleets/FleetAssignmentObserver.php` envoie des notifications (`app/Notifications/Fleets/FleetAssignedNotification.php`) lors de la création, mise à jour ou suppression d'une assignation.
            2.  La commande `app/Console/Commands/Fleets/CheckFleetAssignmentRemindersCommand.php` vérifie quotidiennement les assignations dont la fin approche et envoie des rappels (`app/Notifications/Fleets/FleetAssignmentReminderNotification.php`) aux entités assignées. Cette commande est planifiée via `routes/console.php`.
        - Les modèles `app/Models/Fleets/Fleet`, `app/Models/RH/Employee` et `app/Models/RH/Team` ont des relations polymorphiques (`MorphMany`, `MorphToMany`) pour gérer ces assignations.

---

### Module : Paie

- **Description Fonctionnelle** : Préparation, calcul et export des fiches de paie.
- **Implémentation Technique** :
    - **Service de Calcul** : Le service `app/Services/Paie/PayrollCalculator.php` est le moteur principal. Il collecte les heures (`Timesheet`) et les frais (`Expense`) pour une période donnée, les agrège et crée des `PayrollVariable` pour un `PayrollSlip`. **Il inclut désormais la gestion des différents types d'heures majorées (supplémentaires, nuit, dimanche) pour le calcul.**
    - **Variables de Paie** : L'Enum `app/Enums/Paie/PayrollVariableType.php` est utilisé pour standardiser les différents types d'éléments de paie.
    - **Structure** : Les modèles `PayrollPeriods`, `PayrollSlip`, et `PayrollVariable` forment la structure de base pour stocker les données de paie. Le modèle `PayrollSlip` implémente `HasMedia` et inclut un champ `processed_at`.
    - **Export de Paie** :
        - Le service `app/Services/Paie/PayrollExportService.php` génère un fichier CSV à partir des `PayrollVariable` d'un `PayrollSlip`. Il supporte différents formats d'export (générique, Silae, Sage) via l'Enum `app/Enums/Paie/PayrollExportFormat.php`, avec une logique affinée pour les spécificités de Silae et Sage.
        - Le Job `app/Jobs/Paie/GeneratePayrollExportJob.php` orchestre le calcul via `PayrollCalculator` et l'export via `PayrollExportService`, puis attache le fichier CSV généré au `PayrollSlip` via Spatie Media Library. **Il marque également les notes de frais comme remboursées après le traitement du bulletin de paie.**

---

### Module : GPAO

- **Description Fonctionnelle** : Gestion des Ordres de Fabrication (OF) et de la nomenclature (recettes).
- **Implémentation Technique** :
    - **Nomenclature** : La gestion des "recettes" (assemblages de produits) est en place.
    - **Ordres de Fabrication** :
        - **Modèle Principal** : `app/Models/GPAO/ProductionOrder.php` pour gérer les ordres de fabrication, **incluant les champs de planification (`assigned_to`, `planned_start_date`, `planned_end_date`) et de suivi (`actual_start_date`, `actual_end_date`)**.
        - **Statuts** : `app/Enums/GPAO/ProductionOrderStatus.php` pour suivre l'état de l'OF (Brouillon, Planifié, En cours, Terminé, Annulé).
        - **Automatisation (Création à partir des commandes)** : L'observer `app/Observers/Facturation/SalesDocumentObserver.php` crée automatiquement un OF si le stock est insuffisant lors de la validation d'un devis.
        - **Automatisation (Mise à jour des stocks)** : L'observer `app/Observers/GPAO/ProductionOrderObserver.php` met à jour les stocks (décrémentation des composants, incrémentation du produit fini) lorsque l'OF passe au statut `Completed`.
        - **Automatisation (Notifications)** : L'observer `app/Observers/GPAO/ProductionOrderObserver.php` envoie des notifications (`app/Notifications/GPAO/ProductionOrderNotification.php`) lors de la création, mise à jour (changement de statut ou d'assignation) et suppression d'un OF.
        - **Automatisation (Alertes de retard)** : La commande `app/Console/Commands/GPAO/CheckProductionOrderDelaysCommand.php` vérifie quotidiennement les OF en retard et envoie des alertes. Cette commande est planifiée via `routes/console.php`.
        - **Calcul du Coût de Main-d'Œuvre** : L'observer `app/Observers/RH/TimesheetObserver.php` recalcule le `total_labor_cost` de l'OF à chaque modification d'un pointage lié.
        - **Calcul du Coût des Matériaux** : L'observer `app/Observers/GPAO/ProductionOrderObserver.php` calcule le `total_material_cost` de l'OF lorsque celui-ci est terminé.

---

### Module : Locations

- **Description Fonctionnelle** : Gestion des contrats de location de matériel.
- **Implémentation Technique** :
    - **Structure** :
        - **Modèles** : `app/Models/Locations/RentalContract.php` et `app/Models/Locations/RentalContractLine.php`.
        - **Statuts** : `app/Enums/Locations/RentalContractStatus.php`.
    - **Automatisation (Calcul des Totaux)** : L'observer `app/Observers/Locations/RentalContractLineObserver.php` recalcule les totaux du contrat (`total_ht`, `total_ttc`) à chaque modification d'une ligne.
    - **Automatisation (Comptabilisation)** : L'observer `app/Observers/Locations/RentalContractObserver.php` déclenche le service `app/Services/Comptabilite/RentalContractComptaService.php` lorsque le statut du contrat passe à `Active`.
    - **Intégration Coûts Chantiers** : L'observer `app/Observers/Locations/RentalContractObserver.php` met à jour le `total_rental_cost` sur le `Chantier` associé lors des modifications du contrat.

---

### Module : 3D Vision

- **Description Fonctionnelle** : Visualisation 3D des projets.
- **Implémentation Technique** :
    - **Coordonnées GPS** : La gestion des coordonnées GPS est prête.
    - **Intégration Viewer BIM/IFC** : L'intégration d'un visualiseur BIM/IFC est à faire.

---

### RÉFÉRENCES CODE CLÉS (Pour le Contexte)

| Catégorie | Fichier | Rôle / Utilisation |
|---|---|---|
| RH/Pointage | app/Models/RH/Timesheet.php | Modèle central du pointage, calcule le cost du travail. |
| RH/Automation | app/Observers/RH/TimesheetObserver.php | Logique métier/règles, déclenche le recalcul du coût chantier après chaque modification d'heure. |
| Compta/NDF | app/Services/Comptabilite/ExpenseComptaService.php | Service de comptabilisation des notes de frais, inclut `tier_id`. |
| Compta/NDF | app/Observers/NoteFrais/ExpenseObserver.php | Déclenche la comptabilisation des NDF après validation. |
| Compta/Ulys | app/Services/Comptabilite/UlysComptaService.php | Service de comptabilisation des consommations Ulys, inclut `tier_id`. |
| Compta/FEC | app/Jobs/Comptabilite/GenerateFecJob.php | Génération du Fichier des Écritures Comptables (FEC). |
| Compta/Base | app/Models/Comptabilite/ComptaEntry.php | Modèle d'écriture comptable, inclut `tier_id` et relation `tier`. |
| Compta/Reporting | app/Services/Comptabilite/ComptaReportingService.php | Service de récupération des données pour les journaux et le Grand Livre. |
| Compta/Rapports | app/Console/Commands/Comptabilite/GenerateAccountingReportsCommand.php | Commande Artisan pour générer les rapports comptables (journaux, grand livre) en CSV. Planifiée via `routes/console.php`. |
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
| Locations/Base | app/Models/Locations/RentalContract.php | Modèle principal des contrats de location. |
| Locations/Base | app/Models/Locations/RentalContractLine.php | Modèle pour les lignes de contrat de location. |
| Locations/Automation | app/Observers/Locations/RentalContractLineObserver.php | Recalcule les totaux du contrat à chaque modification d'une ligne. |
| Locations/Automation | app/Observers/Locations/RentalContractObserver.php | Déclenche la comptabilisation du contrat. |
| Locations/Compta | app/Services/Comptabilite/RentalContractComptaService.php | Service de comptabilisation des contrats de location. |
| GPAO/OF | database/migrations/2025_12_12_270000_add_total_material_cost_to_production_orders_table.php | Migration pour ajouter le coût des matériaux aux ordres de fabrication. |
