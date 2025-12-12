# Batistack - Documentation Technique des Modules

Ce document détaille l'implémentation technique et les mécanismes internes de chaque module du projet Batistack. Il est destiné aux développeurs pour comprendre l'architecture, les automatisations et les flux de données.

---

### Module : Pointage / RH

- **Description Fonctionnelle** : Saisie des heures des employés et calcul du coût de la main-d'œuvre pour les chantiers.
- **Implémentation Technique** :
    - **Modèle Principal** : `app/Models/RH/Timesheet.php`. Ce modèle est central et contient la logique de calcul du coût (`cost`) d'une session de travail.
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
    - **Workflow de Validation** : Le processus est géré par des statuts définis dans l'Enum `app/Enums/NoteFrais/ExpenseStatus.php`. L'interface Filament permet aux managers de changer le statut d'une dépense (`Expense`).
    - **Automatisation (Comptabilisation)** : C'est le cœur du processus post-validation.
        1.  L'observer `app/Observers/NoteFrais/ExpenseObserver.php` surveille les changements sur le modèle `Expense`.
        2.  Lorsque le statut passe à `Validated`, l'observer instancie et appelle la méthode `postExpenseEntry()` du service `app/Services/Comptabilite/ExpenseComptaService.php`.
        3.  Ce service encapsule toute la logique comptable : il crée les écritures de débit (charge, TVA) et de crédit (compte du tiers) dans la table `compta_entries` à l'intérieur d'une transaction `DB::transaction()` pour garantir l'intégrité des données.
        4.  Une fois les écritures créées, le service met à jour le statut de l'`Expense` à `Posted`.

---

### Module : Banque & Paiements

- **Description Fonctionnelle** : Gestion des comptes bancaires, synchronisation des transactions et rapprochement.
- **Implémentation Technique** :
    - **Synchronisation Externe** : Un service (probablement `app/Services/Bridge/BridgeService.php`) est utilisé pour communiquer avec l'API BridgeAPI et importer les transactions bancaires. Ce processus est déclenché via une commande ou un Job.
    - **Rapprochement Automatique** : Un Job (tâche en arrière-plan) est responsable de comparer les transactions bancaires importées avec les factures et paiements internes pour les rapprocher.
    - **Mise à jour des Soldes** : Un observer sur le modèle `Payment` met à jour le solde du `BankAccount` associé chaque fois qu'un paiement est marqué comme `cleared` (compensé) ou si un paiement est supprimé.

---

### Module : Comptabilité

- **Description Fonctionnelle** : Centralisation des écritures comptables, génération des journaux et exports légaux.
- **Implémentation Technique** :
    - **Modèle Central** : `app/Models/Comptabilite/ComptaEntry.php` est le modèle qui stocke chaque ligne d'écriture comptable. Il utilise une relation polymorphique (`sourceable`) pour lier une écriture à sa source (ex: une `Expense`, une `Invoice`).
    - **Services de Comptabilisation** : La logique est déportée dans des services spécialisés.
        - `app/Services/Comptabilite/ExpenseComptaService.php` : Gère la transformation d'une note de frais en écritures comptables.
        - `app/Services/Comptabilite/UlysComptaService.php` (en cours) : Gérera la comptabilisation des dépenses de flotte (carburant, péages) importées depuis Ulys.

---

### Module : Flottes

- **Description Fonctionnelle** : Gestion de la flotte de véhicules, des assurances et des consommations.
- **Implémentation Technique** :
    - **Synchronisation Ulys** : La commande `app/Console/Commands/Fleets/SyncUlysConsumptionsCommand.php` utilise le service `app/Services/Fleets/UlysService.php` pour récupérer les données de consommation de l'API Ulys et les stocker dans le modèle `app/Models/Fleets/UlysConsumption.php`.
    - **Maintenance** : La structure de la table `maintenances` est prête (`database/migrations/2025_12_11...create_maintenances_table.php`) pour stocker l'historique et les coûts des entretiens.

---

### Module : Paie

- **Description Fonctionnelle** : Préparation et calcul des fiches de paie.
- **Implémentation Technique** :
    - **Service de Calcul** : Le service `app/Services/PayrollCalculator.php` est le moteur principal. Il sera responsable de collecter toutes les variables de paie pour une période donnée (heures depuis `Timesheet`, frais depuis `Expense`) et de les agréger pour générer une `PayrollSlip`.
    - **Variables de Paie** : L'Enum `app/Enums/Paie/PayrollVariableType.php` est utilisé pour standardiser les différents types d'éléments de paie (heures supplémentaires, primes, paniers repas, etc.).
    - **Structure** : Les modèles `PayrollPeriods`, `PayrollSlip`, et `PayrollVariable` forment la structure de base pour stocker les données de paie.
