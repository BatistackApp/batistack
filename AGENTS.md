CONTEXTE PROJET BATISTACK POUR ASSISTANT IA

Ce document fournit une vue d'ensemble du projet SAAS modulaire Batistack (spécialisé BTP) et son état d'avancement, afin d'optimiser les suggestions de code, les refactorisations et les Q&A techniques.

1. TECHNOLOGIES ET ARCHITECTURE

|

| Élément | Description | Détails Importants |
| Framework | Laravel 11 (PHP) | Architecture modulaire, séparation par domaines (RH, Fleets, Compta, Chantiers...). |
| UI / Admin | Filament PHP | L'intégralité du back-office et de l'interface utilisateur est construite avec Filament. |
| Base de Données | MySQL / PostgreSQL | Utilisation des foreignId, constrained()->cascadeOnDelete(). |
| Multitenancy | Oui | Chaque Model utilise le trait App\Trait\BelongsToCompany et les requêtes doivent être scopées par company_id. |
| GED | Spatie Media Library | Gestion des documents (GED), relations polymorphiques. |
| Conventions | PHP Standards (PSR-12), DocBlocks (PHPDoc). | Utiliser les Enums pour les statuts (TimesheetType, PayrollVariableType). |

2. ÉTAT D'AVANCEMENT DES MODULES

Les modules sont classés selon l'état du développement (référentiel Etat-projet-batistack.docx).

A. MODULES TERMINÉS / STABLES (Production)

| Module | Description Fonctionnelle Clé | Automation / Références Code Clés |
| Tiers (CRM) | Clients, Fournisseurs, Sous-traitants. | Formatage auto. |
| Chantiers | Gestion Projets, Coûts (Main d'œuvre + Frais). | Géocodage auto. Coût main d'œuvre mis à jour par TimesheetObserver.php. |
| Articles & Stock | Catalogue, Ouvrages (Recettes), Multi-dépôts. | Alerte stock bas, Mouvements de stock. |
| Commerce / Facturation | Devis, Factures, Acomptes, Suivi paiements. | Génération PDF (Browsershot), Notifs retards paiement. |
| Banque | Comptes, Transactions, Rapprochement. | Synchro BridgeAPI, Rapprochement auto. des transactions, MAJ auto. du solde. |
| Pointage / RH | Saisie Heures, Calcul Coût Chantier. | Alerte Pointage manquant (CheckMissingTimesheetsCommand.php, MissingTimesheetNotification.php). |
| Notes de Frais | Dépenses, TVA auto, Validation. | Workflow Validation Manager, Comptabilisation auto. post-validation (ExpenseComptaService). |
| GED | Documents, Métadonnées. | Alerte expiration (Assurances). |

B. MODULES EN COURS (Focus Actuel)

| Module | État actuel | Ce qu'il reste à faire / Fichiers récents |
| Comptabilité | Avancé : Comptabilisation auto. des NDF et Ulys (ExpenseComptaService, UlysComptaService). Génération du FEC avec gestion des tiers (GenerateFecJob). Reporting des journaux et Grand Livre (ComptaReportingService). | Finaliser les journaux (vente/achat/banque), Grand Livre. |
| Paie | Avancé : Modèles de base prêts (PayrollPeriods, PayrollSlip, PayrollVariable), Enums (PayrollVariableType), Service de calcul (PayrollCalculator). Génération d'exports CSV (PayrollExportService, GeneratePayrollExportJob) avec support de différents formats (PayrollExportFormat). | Finaliser l'export vers Silae/Sage. |
| Flottes | Avancé : Gestion détaillée des véhicules (Fleet, FleetType). Gestion des assurances avec alertes (Insurance, CheckFleetExpirationsCommand, InsuranceExpiringNotification). Assignation des véhicules aux employés/équipes (FleetAssignment). Gestion des maintenances avec alertes d'échéance (Maintenance, MaintenanceType, CheckMaintenanceAlertsCommand, MaintenanceAlertNotification). | |
| GPAO | Nomenclature (Recette) faite. | Manque la gestion des Ordres de Fabrication (OF). |
| 3D Vision | Coordonnées GPS prêtes. | Manque l'intégration du Viewer BIM/IFC. |

C. MODULES À FAIRE (Priorités Futures)

Locations : Gestion des contrats de location (Interne ou Externe), Planning.

Intervention : Gestion des interventions sur sites ou chantiers.

3. RÉFÉRENCES CODE CLÉS (Pour le Contexte)

| Catégorie | Fichier | Rôle / Utilisation |
|---|---|---|
| RH/Pointage | app/Models/RH/Timesheet.php | Modèle central du pointage, calcule le cost du travail. |
| RH/Automation | app/Observers/RH/TimesheetObserver.php | Logique métier/règles, déclenche le recalcul du coût chantier après chaque modification d'heure. |
| Compta/NDF | app/Services/Comptabilite/ExpenseComptaService.php | Service de comptabilisation des notes de frais, inclut `tier_id`. |
| Compta/NDF | app/Observers/NoteFrais/ExpenseObserver.php | Déclenche la comptabilisation des NDF après validation. |
| Compta/Ulys | app/Services/Comptabilite/UlysComptaService.php | Service de comptabilisation des consommations Ulys, inclut `tier_id`. |
| Compta/FEC | app/Jobs/Comptabilite/GenerateFecJob.php | Génération du Fichier des Écritures Comptables (FEC) avec gestion des tiers. |
| Compta/Base | app/Models/Comptabilite/ComptaEntry.php | Modèle d'écriture comptable, inclut `tier_id` et relation `tier`. |
| Compta/Reporting | app/Services/Comptabilite/ComptaReportingService.php | Service de récupération des données pour les journaux et le Grand Livre. |
| RH/Paie | app/Enums/Paie/PayrollVariableType.php | Enum des variables de paie (Heures, Primes, Frais). |
| Paie/Calcul | app/Services/Paie/PayrollCalculator.php | Service de calcul des fiches de paie (agrégation heures/frais). |
| Paie/Export | app/Services/Paie/PayrollExportService.php | Service de génération du fichier CSV d'export de paie. |
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
| Flottes/Assignation | app/Models/Fleets/FleetAssignment.php | Modèle pour l'assignation polymorphique des véhicules. |
| Flottes/Assignation | database/migrations/2025_12_12...create_fleet_assignments_table.php | Migration pour la table d'assignation des flottes. |
| Flottes/Structure | database/migrations/2025_12_11...create_maintenances_table.php | Stocke les informations de suivi et coût des entretiens. |
| NDF/Structure | database/migrations/2025_12_12...add_reimbursement_fields_to_expenses_table.php | Ajout des champs de remboursement aux notes de frais. |
| Compta/Structure | database/migrations/2025_12_12...add_tier_id_to_compta_entries_table.php | Ajout du champ `tier_id` aux écritures comptables. |

4. RÔLES UTILISATEURS (AGENTS)

(Se référer à la section 1 de ce document pour la hiérarchie des permissions)

| Rôle | Description |
|---|---|
| Direction / Administrateur | Accès complet, gestion des paramètres globaux. |
| Conducteur / Chef d'équipe | Saisie primaire des données (ex: pointage, notes de frais). |
| Gestionnaire de Chantier | Validation, suivi des coûts de projet. |
| Gestionnaire Financier / Administratif | Facturation, rapprochement bancaire, gestion des dépenses. |
| Gestionnaire de Flotte / Matériel | Gestion des véhicules, engins, maintenances, assurances. |
