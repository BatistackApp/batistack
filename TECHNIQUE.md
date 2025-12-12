# Batistack - Documentation Technique des Modules

Ce document détaille l'implémentation technique et les mécanismes internes de chaque module du projet Batistack. Il est destiné aux développeurs pour comprendre l'architecture, les automatisations et les flux de données.

---

### Module : Pointage / RH

- **Description Fonctionnelle** : Saisie des heures des employés et calcul du coût de la main-d'œuvre pour les chantiers.
- **Implémentation Technique** :
    - **Modèle Principal** : `app/Models/RH/Timesheet.php`. Ce modèle est central et contient la logique de calcul du coût (`cost`) d'une session de travail.
    - **Types d'Heures** : Utilise l'Enum `app/Enums/RH/TimesheetType.php` pour catégoriser les types de pointage (Travail, Trajet, Absence, Formation).
    - **Automatisation (Pointages Manquants)** : Une tâche planifiée (cron) exécute la commande `app/Console/Commands/CheckMissingTimesheetsCommand.php`. Cette commande identifie les employés n'ayant pas rempli leurs heures et leur envoie une notification via `app/Notifications/MissingTimesheetNotification.php`.

---

### Module : Chantiers

- **Description Fonctionnelle** : Gestion des projets, de leurs coûts et de leur localisation.
- **Implémentation Technique** :
    - **Automatisation (Calcul des Coûts)** : Le coût total de main-d'œuvre d'un chantier est mis à jour automatiquement. L'observer `app/Observers/RH/TimesheetObserver.php` écoute les événements `created`, `updated`, `deleted` du modèle `Timesheet`. À chaque événement, il déclenche le recalcul du coût total sur le modèle `Chantier` associé.
    - **Géocodage** : Une automatisation (probablement un observer sur le modèle `Chantier`) convertit l'adresse d'un chantier en coordonnées GPS lors de sa création ou modification.

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

### Module : Banque & Paiements

- **Description Fonctionnelle** : Gestion des comptes bancaires, synchronisation des transactions et rapprochement.
- **Implémentation Technique** :
    - **Synchronisation Externe** : Un service (probablement `app/Services/Bridge/BridgeService.php`) est utilisé pour communiquer avec l'API BridgeAPI et importer les transactions bancaires. Ce processus est déclenché via une commande ou un Job.
    - **Rapprochement Automatique** : Un Job (tâche en arrière-plan) est responsable de comparer les transactions bancaires importées avec les factures et paiements internes pour les rapprocher.
    - **Mise à jour des Soldes** : Un observer sur le modèle `Payment` met à jour le solde du `BankAccount` associé chaque fois qu'un paiement est marqué comme `cleared` (compensé) ou si un paiement est supprimé.

---

### Module : Comptabilité

- **Description Fonctionnelle** : Centralisation des écritures comptables, génération des journaux et exports légaux (FEC).
- **Implémentation Technique** :
    - **Modèle Central** : `app/Models/Comptabilite/ComptaEntry.php` est le modèle qui stocke chaque ligne d'écriture comptable. Il utilise une relation polymorphique (`sourceable`) pour lier une écriture à sa source (ex: une `Expense`, une `Invoice`). Il inclut désormais un `tier_id` pour l'association directe avec un `Tiers`.
    - **Services de Comptabilisation** : La logique est déportée dans des services spécialisés.
        - `app/Services/Comptabilite/ExpenseComptaService.php` : Gère la comptabilisation des notes de frais, incluant l'association du `tier_id`.
        - `app/Services/Comptabilite/UlysComptaService.php` : Gère la comptabilisation des dépenses de flotte Ulys, incluant l'association du `tier_id`.
    - **Export FEC** :
        - Le Job `app/Jobs/Comptabilite/GenerateFecJob.php` est responsable de la génération du Fichier des Écritures Comptables (FEC).
        - Il utilise les relations `journal`, `account` et `tier` pour extraire les données.
        - Il remplit les champs `CompAuxNum` et `CompAuxLib` avec les informations du `Tiers` associé à l'écriture.

---

### Module : Flottes

- **Description Fonctionnelle** : Gestion de la flotte de véhicules, des assurances, des consommations et des assignations.
- **Implémentation Technique** :
    - **Modèle Principal** : `app/Models/Fleets/Fleet.php` est enrichi avec des détails comme `name`, `registration_number`, `type` (via `app/Enums/Fleets/FleetType.php`), `brand`, `model`, `vin`, `mileage`.
    - **Synchronisation Ulys** : La commande `app/Console/Commands/Fleets/SyncUlysConsumptionsCommand.php` utilise le service `app/Services/Fleets/UlysService.php` pour récupérer les données de consommation de l'API Ulys et les stocker dans le modèle `app/Models/Fleets/UlysConsumption.php`. Le modèle `UlysConsumption` inclut un `status` (`Pending`, `Posted`) pour le suivi comptable.
    - **Gestion des Assurances** :
        - Le modèle `app/Models/Fleets/Insurance.php` est enrichi avec `contract_number`, `insurer_name`, `is_active`, et `notified_at`.
        - La commande `app/Console/Commands/Fleets/CheckFleetExpirationsCommand.php` vérifie les assurances expirant bientôt.
        - Elle envoie des notifications via `app/Notifications/Fleets/InsuranceExpiringNotification.php` aux utilisateurs concernés.
    - **Assignation des Véhicules** :
        - La table `fleet_assignments` et le modèle `app/Models/Fleets/FleetAssignment.php` gèrent l'historique des assignations.
        - Les modèles `app/Models/Fleets/Fleet`, `app/Models/RH/Employee` et `app/Models/RH/Team` ont des relations polymorphiques (`MorphMany`, `MorphToMany`) pour gérer ces assignations.
    - **Maintenance** : La structure de la table `maintenances` est prête (`database/migrations/2025_12_11...create_maintenances_table.php`) pour stocker l'historique et les coûts des entretiens.

---

### Module : Paie

- **Description Fonctionnelle** : Préparation, calcul et export des fiches de paie.
- **Implémentation Technique** :
    - **Service de Calcul** : Le service `app/Services/Paie/PayrollCalculator.php` est le moteur principal. Il collecte les heures (`Timesheet`) et les frais (`Expense`) pour une période donnée, les agrège et crée des `PayrollVariable` pour un `PayrollSlip`.
    - **Variables de Paie** : L'Enum `app/Enums/Paie/PayrollVariableType.php` est utilisé pour standardiser les différents types d'éléments de paie.
    - **Structure** : Les modèles `PayrollPeriods`, `PayrollSlip`, et `PayrollVariable` forment la structure de base pour stocker les données de paie. Le modèle `PayrollSlip` implémente `HasMedia` et inclut un champ `processed_at`.
    - **Export de Paie** :
        - Le service `app/Services/Paie/PayrollExportService.php` génère un fichier CSV à partir des `PayrollVariable` d'un `PayrollSlip`.
        - Le Job `app/Jobs/Paie/GeneratePayrollExportJob.php` orchestre le calcul via `PayrollCalculator` et l'export via `PayrollExportService`, puis attache le fichier CSV généré au `PayrollSlip` via Spatie Media Library.

---

### Module : GPAO

- **Description Fonctionnelle** : Gestion des Ordres de Fabrication (OF) et de la nomenclature (recettes).
- **Implémentation Technique** :
    - **Nomenclature** : La gestion des "recettes" (assemblages de produits) est en place.
    - **Ordres de Fabrication** : Le développement des modèles et de la logique pour les OF est à faire.

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
| RH/Paie | app/Enums/Paie/PayrollVariableType.php | Enum des variables de paie (Heures, Primes, Frais). |
| Paie/Calcul | app/Services/Paie/PayrollCalculator.php | Service de calcul des fiches de paie (agrégation heures/frais). |
| Paie/Export | app/Services/Paie/PayrollExportService.php | Service de génération du fichier CSV d'export de paie. |
| Paie/Job | app/Jobs/Paie/GeneratePayrollExportJob.php | Orchestre le calcul et l'export de paie. |
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
| Flottes/Assignation | app/Models/Fleets/FleetAssignment.php | Modèle pour l'assignation polymorphique des véhicules. |
| Flottes/Structure | database/migrations/2025_12_11...create_maintenances_table.php | Stocke les informations de suivi et coût des entretiens. |
| NDF/Structure | database/migrations/2025_12_12...add_reimbursement_fields_to_expenses_table.php | Ajout des champs de remboursement aux notes de frais. |
| Compta/Structure | database/migrations/2025_12_12...add_tier_id_to_compta_entries_table.php | Ajout du champ `tier_id` aux écritures comptables. |
