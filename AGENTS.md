CONTEXTE PROJET BATISTACK POUR ASSISTANT IA

Ce document fournit une vue d'ensemble du projet SAAS modulaire Batistack (spécialisé BTP) et son état d'avancement, afin d'optimiser les suggestions de code, les refactorisations et les Q&A techniques.

1. TECHNOLOGIES ET ARCHITECTURE

|

| Élément | Description | Détails Importants |
| Framework | Laravel 12 (PHP) | Architecture modulaire, séparation par domaines (RH, Fleets, Compta, Chantiers...). |
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
| Comptabilité | Avancé : Comptabilisation auto. des NDF, consommations Ulys, **factures de vente, factures fournisseurs et contrats de location**. Génération du FEC avec gestion des tiers et numérotation séquentielle conforme. Reporting des journaux et Grand Livre, avec **génération automatique de rapports CSV**. | Finaliser les journaux (vente/achat/banque), Grand Livre. |
| Paie | Avancé : Calcul des fiches de paie (agrégation heures/frais), **incluant les notes de frais remboursables et la gestion des heures majorées**. Génération d'exports CSV avec support de différents formats (Silae, Sage, générique), **prêt pour l'intégration des spécifications exactes**. | Finaliser l'export vers Silae/Sage. |
| Flottes | Avancé : Gestion détaillée des véhicules (immatriculation, type, marque, modèle, VIN, kilométrage). Gestion des assurances avec alertes d'expiration. Gestion des maintenances avec alertes d'échéance. Assignation des véhicules aux employés ou équipes, **avec suivi de statut et rappels de fin d'assignation**. | |
| GPAO | Avancé : Gestion des ordres de fabrication, **incluant la création automatique à partir des commandes clients**, la planification, le suivi de statut, la mise à jour des stocks, le calcul du coût de la main-d'œuvre et des matériaux, et les notifications d'assignation et de retard. | |
| Locations | En cours : Gestion des contrats de location (fournisseurs), avec calcul des totaux et comptabilisation automatique. | |
| Interventions | En cours : Gestion des interventions, avec suivi des coûts (main-d'œuvre, matériaux), comptabilisation et génération de factures avec marge configurable. | |

C. MODULES À FAIRE (Priorités Futures)

| Module | État actuel | Ce qu'il reste à faire / Fichiers récents |
|---|---|---|

3. RÉFÉRENCES CODE CLÉS (Pour le Contexte)

| Catégorie | Fichier | Rôle / Utilisation |
|---|---|---|
| RH/Pointage | app/Models/RH/Timesheet.php | Modèle central du pointage, calcule le cost du travail. |
| RH/Automation | app/Observers/RH/TimesheetObserver.php | Logique métier/règles, déclenche le recalcul du coût chantier après chaque modification d'heure. |
| Compta/NDF | app/Services/Comptabilite/ExpenseComptaService.php | Service de comptabilisation des notes de frais, inclut `tier_id`. |
| Compta/NDF | app/Observers/NoteFrais/ExpenseObserver.php | Déclenche la comptabilisation des NDF après validation. |
| Compta/Ulys | app/Services/Comptabilite/UlysComptaService.php | Service de comptabilisation des consommations Ulys, inclut `tier_id`. |
| Compta/FEC | app/Jobs/Comptabilite/GenerateFecJob.php | Génération du Fichier des Écritures Comptables (FEC) avec gestion des tiers et numérotation séquentielle conforme. |
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
| Interventions/Base | app/Models/Interventions/Intervention.php | Modèle principal des interventions. |
| Interventions/Base | app/Models/Interventions/InterventionProduct.php | Modèle pivot pour les produits utilisés dans une intervention. |
| Interventions/Automation | app/Observers/Interventions/InterventionObserver.php | Déclenche les notifications et la comptabilisation. |
| Interventions/Automation | app/Observers/Interventions/InterventionProductObserver.php | Recalcule le coût des matériaux et met à jour les stocks. |
| Interventions/Compta | app/Services/Comptabilite/InterventionComptaService.php | Service de comptabilisation des coûts des interventions. |
| Interventions/Notifications | app/Notifications/Interventions/InterventionNotification.php | Notification pour les interventions. |

4. RÔLES UTILISATEURS (AGENTS)

(Se référer à la section 1 de ce document pour la hiérarchie des permissions)

| Rôle | Description |
|---|---|
| Direction / Administrateur | Accès complet, gestion des paramètres globaux. |
| Conducteur / Chef d'équipe | Saisie primaire des données (ex: pointage, notes de frais). |
| Gestionnaire de Chantier | Validation, suivi des coûts de projet. |
| Gestionnaire Financier / Administratif | Facturation, rapprochement bancaire, gestion des dépenses. |
| Gestionnaire de Flotte / Matériel | Gestion des véhicules, engins, maintenances, assurances. |
| Responsable GPAO | Gestion des ordres de fabrication, planification, suivi de production. |
| Opérateur de Production | Consultation des ordres de fabrication assignés, saisie des temps de production. |
| Technicien | Consultation et suivi des interventions assignées. |
