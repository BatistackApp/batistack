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
| Comptabilité | Avancé : Comptabilisation auto. des NDF via ExpenseComptaService. Logique Ulys démarrée. | Finaliser les journaux (vente/achat/banque), Grand Livre, Export FEC. Intégrer la logique UlysComptaService. |
| Paie | Avancé : Modèles de base prêts (PayrollPeriods, PayrollSlip, PayrollVariable), Enums (PayrollVariableType), Service de calcul (PayrollCalculator). | Finaliser le service PayrollCalculator (import des heures/frais) et créer l'export final vers Silae/Sage. |
| Flottes | Démarré : Gestion de la Maintenance. | Gérer les véhicules, engins, assurances, attribution aux équipes. La table maintenances existe (2025_12_11_194308_create_maintenances_table.php). |
| GPAO | Nomenclature (Recette) faite. | Manque la gestion des Ordres de Fabrication (OF). |
| 3D Vision | Coordonnées GPS prêtes. | Manque l'intégration du Viewer BIM/IFC. |

C. MODULES À FAIRE (Priorités Futures)

Locations : Gestion des contrats de location (Interne ou Externe), Planning.

Intervention : Gestion des interventions sur sites ou chantiers.

3. RÉFÉRENCES CODE CLÉS (Pour le Contexte)

| Catégorie | Fichier | Rôle / Utilisation |
| RH/Pointage | app/Models/RH/Timesheet.php | Modèle central du pointage, calcule le cost du travail. |
| RH/Automation | app/Observers/RH/TimesheetObserver.php | Logique métier/règles, déclenche le recalcul du coût chantier après chaque modification d'heure. |
| Compta/NDF | app/Services/Comptabilite/ExpenseComptaService.php | Service de comptabilisation des notes de frais. |
| Compta/NDF | app/Observers/NoteFrais/ExpenseObserver.php | Déclenche la comptabilisation des NDF après validation. |
| RH/Paie | app/Enums/Paie/PayrollVariableType.php | Enum des variables de paie (Heures, Primes, Frais). |
| Paie | app/Services/PayrollCalculator.php | Service de calcul des fiches de paie. |
| Paie | database/migrations/2025_12_10...create_payroll_slips_table.php | Structure de la fiche de paie. |
| Flottes | database/migrations/2025_12_11...create_maintenances_table.php | Stocke les informations de suivi et coût des entretiens. |
| Flottes | app/Console/Commands/Fleets/SyncUlysConsumptionsCommand.php | Synchronisation des consommations Ulys. |
| Flottes | app/Models/Fleets/UlysConsumption.php | Modèle pour les consommations Ulys. |
| Flottes | app/Services/Fleets/UlysService.php | Service d'intégration avec l'API Ulys. |

4. RÔLES UTILISATEURS (AGENTS)

(Se référer à la section 1 de ce document pour la hiérarchie des permissions)

| Rôle | Description |
|---|---|
| Direction / Administrateur | Accès complet, gestion des paramètres globaux. |
| Conducteur / Chef d'équipe | Saisie primaire des données (ex: pointage, notes de frais). |
| Gestionnaire de Chantier | Validation, suivi des coûts de projet. |
| Gestionnaire Financier / Administratif | Facturation, rapprochement bancaire, gestion des dépenses. |
| Gestionnaire de Flotte / Matériel | Gestion des véhicules, engins, maintenances, assurances. |
