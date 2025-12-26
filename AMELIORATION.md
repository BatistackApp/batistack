# Pistes d'Amélioration - Batistack

Ce document centralise les pistes d'amélioration et les refactorisations potentielles identifiées lors des phases de vérification des modules.

## Module : Tiers (CRM)

1.  **Validation des Données** :
    *   **Contexte** : L'Observer `TiersObserver` effectue un nettoyage basique. Pour augmenter la qualité des données, une validation plus poussée pourrait être implémentée.
    *   **Suggestion** : Dans `TiersObserver` ou via des `Rules` Laravel dédiées, ajouter une validation sur le format des champs `vat_number` et `siret_number` en fonction du pays du tiers (ex: utiliser une librairie de validation de n° de TVA européen).

2.  **Gestion du Rôle "Employé"** :
    *   **Contexte** : `TierNature` peut être `Employee`, ce qui est une approche pour unifier la gestion des tiers (ex: pour les notes de frais).
    *   **Suggestion** : S'assurer qu'un `Observer` ou un `Job` synchronise la création/mise à jour d'un `Employee` (module RH) avec la création/mise à jour d'un `Tiers` de nature `Employee`. Cela garantirait que chaque employé a son "double" tiers pour les opérations comptables, sans saisie manuelle.

---

## Module : Chantiers

1.  **Consistance des Migrations** :
    *   **Contexte** : Les ajouts de colonnes à la table `chantiers` ne suivent pas un pattern de nommage clair, rendant la recherche difficile. La définition des colonnes `latitude`/`longitude` est invalide (`string` au lieu de `decimal`) dans la migration initiale.
    *   **Suggestion** : Adopter une convention stricte pour les migrations (ex: `add_total_fleet_cost_to_chantiers_table`). Créer une nouvelle migration "corrective" (`fix_columns_type_in_chantiers_table`) pour changer le type de `latitude` et `longitude` de `string` à `decimal` pour garantir l'intégrité des données.

2.  **Performance des Accesseurs de Coûts** :
    *   **Contexte** : Les accesseurs comme `getTotalRealCostAttribute` sont très pratiques mais peuvent causer des problèmes de performance sur de grosses listes (N+1 implicites si les relations ne sont pas chargées). Le `DashboardService` charge toute la collection en mémoire (`->get()`) avant de trier, ce qui est inefficace sur des milliers de chantiers.
    *   **Suggestion** : Pour les listes et les rapports, créer des **scopes de requête** dans le modèle `Chantiers` qui effectuent les calculs de totaux directement en SQL (ex: `withRealMargin()`). Le `DashboardService` pourrait alors faire `Chantiers::withRealMargin()->orderBy('real_margin', 'desc')->limit(5)->get()`, ce qui serait beaucoup plus performant en déléguant le calcul à la base de données.

---

## Module : Articles & Stock

1.  **Automatisation de la Création de Stock** :
    *   **Contexte** : Actuellement, lors de la création d'un nouveau produit stockable, il faut manuellement créer ses entrées de stock dans chaque dépôt.
    *   **Suggestion** : Créer un `ProductObserver`. Sur l'événement `created`, si le produit est `is_stockable`, l'observer pourrait automatiquement créer une ligne `InventoryStock` avec 0 en quantité dans chaque `Warehouse` existant pour cette compagnie (ou au minimum dans le dépôt par défaut). Cela simplifierait grandement l'initialisation des produits.

2.  **Traçabilité des Mouvements de Stock** :
    *   **Contexte** : Le système gère l'état du stock, mais pas l'historique des mouvements. Savoir *pourquoi* une quantité a changé est crucial pour l'inventaire et l'analyse des écarts.
    *   **Suggestion** : Créer une nouvelle table `stock_movements` avec des champs comme `product_id`, `warehouse_id`, `quantity_change` (ex: -5, +10), `reason` (ex: "Vente Facture #123", "OF #456 Terminé", "Inventaire Manuel"), et une relation polymorphique `sourceable` vers l'objet qui a causé le mouvement. Un `InventoryStockObserver` pourrait alors créer une entrée dans cette table à chaque fois que `quantity_on_hand` est modifié.

---

## Module : Commerce / Facturation

1.  **Compléter le Module Achats** :
    *   **Contexte** : Il manque la gestion détaillée des factures d'achat. On ne peut pas saisir les produits achetés, seulement un montant total.
    *   **Suggestion** :
        1.  Créer la migration `create_purchase_document_lines_table` (similaire à `sales_document_lines`).
        2.  Créer le modèle `PurchaseDocumentLine`.
        3.  Ajouter la relation `lines()` et une méthode `recalculate()` au modèle `PurchaseDocument`.
        4.  Créer un `PurchaseDocumentLineObserver` qui met à jour les totaux du document parent.
        5.  **Bonus** : Sur validation d'une facture d'achat, l'observer pourrait déclencher un `Job` pour mettre à jour le `buying_price` du `Product` (le dernier prix d'achat constaté).

2.  **Fiabiliser la Référence Fournisseur** :
    *   **Contexte** : La référence de facture fournisseur n'est pas unique par compagnie, ce qui causera un bug en production.
    *   **Suggestion** : Créer une migration pour modifier la clé unique sur la table `purchase_documents` de `['reference']` à `['company_id', 'tiers_id', 'reference']` pour garantir l'unicité.

---

## Module : Banque

1.  **Fiabiliser la Mise à Jour du Solde** :
    *   **Contexte** : La méthode `updateBalance` dans `BankAccount` utilise `increment`/`decrement` d'une manière qui peut être source d'erreurs et de *race conditions*.
    *   **Suggestion** : Remplacer la logique dans `updateBalance` par une mise à jour atomique et plus simple : `DB::table('bank_accounts')->where('id', $this->id)->update(['current_balance' => DB::raw("current_balance + {$amountToChange}")]);`. C'est plus sûr et délègue l'opération à la base de données.

2.  **Robustifier la Détection du Sens du Paiement** :
    *   **Contexte** : L'accesseur `getIsIncomingAttribute` dans `Payment` se base sur le nom de la classe `payable_type`, ce qui est fragile.
    *   **Suggestion** : Créer une interface `Payable` avec une méthode `isIncomingPayment(): bool`. Les modèles `SalesDocument` et `PurchaseDocument` implémenteraient cette interface. Le code deviendrait `$payment->payable->isIncomingPayment()`, ce qui est plus robuste.

3.  **Comptabilisation des Paiements** :
    *   **Contexte** : Le `BankTransactionComptaService` comptabilise la transaction brute dans un compte d'attente (471). Il manque le service qui comptabilise le *paiement* lui-même pour lettrer le compte Tiers et solder le compte d'attente.
    *   **Suggestion** : Créer un `PaymentComptaService` qui serait appelé par le `PaymentObserver` lors du rapprochement. Ce service passerait l'écriture : Débit 471 / Crédit 411 (pour un client) ou Débit 401 / Crédit 471 (pour un fournisseur), finalisant ainsi le cycle de rapprochement comptable.

---

## Module : Pointage / RH

1.  **Correction de la Coquille dans l'Observer** :
    *   **Contexte** : Dans `TimesheetObserver`, la mise à jour du compteur d'heures de l'engin se fait avec l'ancienne valeur au lieu de la nouvelle.
    *   **Suggestion** : Corriger la ligne `$fleet->updateQuietly(['hours_meter' => $fleet->hours_meter,]);` par `$fleet->updateQuietly(['hours_meter' => $timesheet->hours_read,]);`.

2.  **Validation des Pointages en Double** :
    *   **Contexte** : Actuellement, rien n'empêche un employé de saisir deux fiches de pointage pour le même jour. On pourrait vouloir empêcher deux saisies du *même type* pour le même jour.
    *   **Suggestion** : Dans `TimesheetObserver` (méthode `saving`), ajouter une vérification pour empêcher la création d'un pointage s'il en existe déjà un pour le même employé, à la même date, avec le même type.

3.  **Gestion des Paniers Repas / Trajets** :
    *   **Contexte** : Les champs `lunch_basket` et `travel_zone` sont des booléens qui doivent être interprétés par la logique de paie.
    *   **Suggestion** : Documenter clairement ou créer un `Trait` ou `Service` qui encapsule la logique de "valorisation" d'un `Timesheet`. Ce service prendrait un `Timesheet` en entrée et retournerait un tableau de `PayrollVariable` (ex: `['type' => 'std_hour', 'quantity' => 8]`, `['type' => 'bonus', 'code' => 'PANIER', 'quantity' => 1]`). Cela découplerait la saisie de la logique de paie.

---

## Module : Notes de Frais

1.  **Refactorisation de l'Observer** :
    *   **Contexte** : La logique de comptabilisation est dans la méthode `updated` de l'observer, ce qui peut être moins intuitif que `saved`.
    *   **Suggestion** : Déplacer la logique de déclenchement du `ExpenseComptaService` de la méthode `updated` vers la méthode `saved`, à l'intérieur de la condition `if ($expense->isDirty('status') && $expense->status === ExpenseStatus::Approved)`. Cela regrouperait toutes les actions post-sauvegarde au même endroit.

2.  **Gestion des Justificatifs** :
    *   **Contexte** : La migration mentionne un `proof_path` mais le code semble plutôt orienté vers Spatie Media Library. Il y a une légère incohérence.
    *   **Suggestion** : Confirmer l'utilisation de Spatie Media Library. Ajouter une `Rule` de validation pour s'assurer qu'une note de frais soumise a **obligatoirement** un justificatif attaché. On pourrait aussi ajouter une validation dans l'observer (`saving`) qui empêcherait le passage au statut `Submitted` si aucun média n'est présent.

3.  **Refacturation au client** :
    *   **Contexte** : Le champ `is_billable` existe mais aucune logique ne semble l'exploiter pour refacturer la dépense au client final.
    *   **Suggestion** : Dans `ExpenseObserver`, si une dépense `is_billable` est `Approved`, on pourrait automatiquement ajouter une ligne au `SalesDocument` (facture) en cours pour le chantier concerné. Cela automatiserait la refacturation des frais engagés pour le compte du client.

---

## Module : GED

1.  **Gestion des Permissions d'Accès** :
    *   **Contexte** : Actuellement, rien ne semble restreindre l'accès à un document. Un utilisateur d'une compagnie peut potentiellement voir tous les documents de cette compagnie.
    *   **Suggestion** : Implémenter une logique de permissions plus fine. On pourrait ajouter une table pivot `document_user` ou utiliser un système de `Policy` Laravel plus avancé. Par exemple, un `DocumentPolicy` pourrait vérifier si l'utilisateur a le droit de voir l'entité `documentable` (ex: "L'utilisateur peut-il voir ce Chantier ? Si oui, il peut voir les documents associés.").

2.  **Recherche Full-Text** :
    *   **Contexte** : La recherche se fait probablement sur le nom ou la description du document. Pour une GED avancée, il est crucial de pouvoir rechercher dans le contenu des fichiers (PDF, DOCX).
    *   **Suggestion** : Intégrer **Laravel Scout** avec un driver comme **MeiliSearch** ou **Algolia**. Spatie Media Library a des intégrations pour faciliter l'indexation du contenu des fichiers.

3.  **Catégorisation par Tags** :
    *   **Contexte** : La structure de dossiers est bonne, mais la recherche transversale est limitée. Un document ne peut être que dans un seul dossier.
    *   **Suggestion** : Ajouter un système de "Tags" (étiquettes). On pourrait utiliser un package comme `spatie/laravel-tags`. Un utilisateur pourrait alors taguer un document avec "Assurance RC Pro", "Contrat 2024", etc., et filtrer rapidement tous les documents avec un certain tag.

---

## Module : Comptabilité

1.  **Gestion du Lettrage** :
    *   **Contexte** : Le champ `lettrage` existe dans le FEC, mais aucune logique ne semble gérer le lettrage automatique ou manuel des comptes.
    *   **Suggestion** : Créer un `LettrageService` ou une commande interactive. Ce service pourrait, pour un compte auxiliaire donné (ex: un client), trouver des groupes d'écritures dont la somme Débit - Crédit est égale à zéro et leur assigner un code de lettrage unique (ex: 'AA', 'AB', etc.).

2.  **Plan de Compte par Défaut** :
    *   **Contexte** : Actuellement, chaque nouvelle `Company` doit probablement se créer son propre plan comptable.
    *   **Suggestion** : Lors de la création d'une nouvelle `Company`, un `Job` ou un `Observer` pourrait automatiquement peupler la table `compta_accounts` avec un plan comptable standard (PCG français).

3.  **Gestion de la TVA** :
    *   **Contexte** : La TVA est calculée et stockée, mais il n'y a pas de processus de déclaration de TVA.
    *   **Suggestion** : Créer une commande `GenerateVatReportCommand` qui, pour une période donnée, calculerait la TVA collectée (depuis les `ComptaEntry` sur les comptes de classe 7) et la TVA déductible (depuis les `ComptaEntry` sur les comptes de classe 6).

---

## Module : Paie

1.  **Centraliser la Logique des Codes de Paie** :
    *   **Contexte** : Le `PayrollCalculator` génère des codes de paie en dur (`HN`, `HS25`...) tandis que le `PayrollExportService` utilise un mapping configurable. C'est redondant.
    *   **Suggestion** : Supprimer la méthode `getPayrollCode` du `PayrollCalculator`. Le champ `code` dans la table `payroll_variables` ne devrait contenir que le type interne (`std_hour`, `overtime_25`...). C'est le `PayrollExportService` qui, au moment de l'export, doit utiliser son `code_mapping` pour traduire le type interne en code externe (`HN`, `100`...).

2.  **Gestion des Primes et Indemnités (Paniers, Trajets)** :
    *   **Contexte** : Le `PayrollCalculator` traite les heures et les notes de frais, mais pas les booléens `lunch_basket` ou `travel_zone` des `Timesheet` pour les transformer en primes.
    *   **Suggestion** : Dans `PayrollCalculator::processTimesheets`, après avoir groupé les heures, faire une autre requête pour compter le nombre de `lunch_basket = true` et `travel_zone = true` sur la période. Ensuite, créer des `PayrollVariable` de type `Bonus` avec un code spécifique ('PANIER', 'TRAJET') et une quantité (le nombre d'occurrences).

3.  **Comptabilisation de la Paie** :
    *   **Contexte** : Le module génère les exports, mais ne passe pas les écritures de paie en comptabilité (OD de salaires).
    *   **Suggestion** : Créer un `PayrollComptaService`. Après la validation d'une période de paie (`PayrollPeriod`), un `Job` pourrait appeler ce service. Il agrégerait toutes les variables de tous les bulletins de la période pour générer une seule OD de paie complexe (Débit des comptes de charge 641xxx, Crédit des comptes de tiers 421xxx pour le net à payer, Crédit des comptes d'organismes sociaux 43xxxx pour les charges, etc.).

---

## Module : Flottes

1.  **Gestion des Coûts de Maintenance et d'Assurance** :
    *   **Contexte** : Les modèles `Maintenance` et `Insurance` ont un champ `cost`. Ce coût est saisi mais n'est pas automatiquement imputé au coût global du véhicule ou à un centre de coût.
    *   **Suggestion** : Créer un `FleetCost` modèle polymorphique. Un `MaintenanceObserver` et un `InsuranceObserver`, sur l'événement `created`, pourraient créer une entrée `FleetCost` liée. Le coût total d'un véhicule serait alors la somme de ses `FleetCost`, ce qui permettrait de calculer un TCO (Total Cost of Ownership) plus précis.

2.  **Améliorer la Logique de Notification d'Assignation** :
    *   **Contexte** : Dans `FleetAssignmentObserver`, la notification est envoyée à tous les membres d'une équipe. Si l'équipe est grande, cela peut être bruyant.
    *   **Suggestion** : Ajouter un champ `manager_id` ou `leader_id` sur le modèle `Team` (il existe déjà) et ne notifier que ce leader. On pourrait aussi ajouter une préférence de notification sur le modèle `User`.

3.  **Dépréciation Automatique** :
    *   **Contexte** : Le modèle `Fleet` a un champ `current_value`. Ce champ est probablement mis à jour manuellement.
    *   **Suggestion** : Créer un `Job` mensuel `DepreciateFleetValueJob`. Ce job parcourrait tous les véhicules et appliquerait une règle de dépréciation (ex: linéaire sur 5 ans) pour mettre à jour automatiquement le champ `current_value`.

---

## Module : Locations

1.  **Fiabiliser la Référence Contrat** :
    *   **Contexte** : La référence de contrat n'est pas unique par compagnie.
    *   **Suggestion** : Créer une migration pour modifier la clé unique sur la table `rental_contracts` de `['reference']` à `['company_id', 'reference']`.

2.  **Améliorer la Séquence des Factures Fournisseurs** :
    *   **Contexte** : Les factures générées par `GenerateRentalSupplierInvoicesCommand` ont une référence basée sur la date, ce qui n'est pas séquentiel.
    *   **Suggestion** : Ajouter un champ `invoice_sequence` au modèle `RentalContract`. À chaque génération de facture, on incrémente ce compteur et on l'utilise dans la référence (`LOC-{$contract->id}-{$sequence}`).

3.  **Comptabilisation du Contrat** :
    *   **Contexte** : La logique de comptabilisation dans `RentalContractObserver` est en `TODO` et ne gère que le cas "Completed".
    *   **Suggestion** : La comptabilisation ne devrait pas être déclenchée par le contrat, mais par la **facture fournisseur (`PurchaseDocument`)** qu'il génère. Le `PurchaseDocumentObserver` existant fait déjà ce travail. La logique dans `RentalContractObserver` peut donc être supprimée.

---

## Module : Interventions

1.  **Signature du Rapport d'Intervention** :
    *   **Contexte** : Le modèle `Intervention` a un champ `report` (texte), mais il n'y a pas de processus de validation/signature par le client.
    *   **Suggestion** : Ajouter la possibilité de générer un PDF du rapport d'intervention. Intégrer un package comme `spatie/laravel-signature-pad` pour permettre au client de signer le rapport directement sur une tablette. Le statut de l'intervention pourrait alors passer à "Approuvé par le client".

2.  **Planification et Calendrier** :
    *   **Contexte** : L'intervention a des dates planifiées, mais il n'y a pas de vue calendrier globale pour voir la charge de travail des techniciens.
    *   **Suggestion** : Créer un `ScheduleService` qui pourrait générer un flux de données (JSON) compatible avec une librairie de calendrier frontend (ex: FullCalendar).

3.  **Gestion des Produits "Non Stockés"** :
    *   **Contexte** : L'`InterventionProductObserver` tente de déstocker chaque produit. Un technicien peut utiliser une fourniture non gérée en stock.
    *   **Suggestion** : Dans le pivot `InterventionProduct`, ajouter un champ `is_inventoried` (booléen). Si ce champ est à `false`, l'observer ne tenterait pas de faire de mouvement de stock, mais le coût du produit serait quand même ajouté au `total_material_cost` de l'intervention.

---

## Module : GPAO

1.  **Fiabiliser la Référence de l'OF** :
    *   **Contexte** : La référence de l'Ordre de Fabrication n'est pas unique par compagnie.
    *   **Suggestion** : Créer une migration pour modifier la clé unique sur la table `production_orders` de `['reference']` à `['company_id', 'reference']`.

2.  **Gestion des "Déchets" ou "Pertes"** :
    *   **Contexte** : Le système suppose que la production est parfaite (100% de rendement). En réalité, il y a des pertes de matière.
    *   **Suggestion** : Ajouter un champ `waste_percentage` sur le modèle `ProductAssembly` (la nomenclature). Lors du calcul des besoins en composants, le système pourrait automatiquement majorer les quantités nécessaires de ce pourcentage.

3.  **Planification Avancée (Gantt)** :
    *   **Contexte** : La planification se limite à des dates de début/fin. Pour une GPAO avancée, une vue de type Gantt est souvent nécessaire pour visualiser les dépendances entre les OF.
    *   **Suggestion** : Créer un `GanttService` qui génère les données nécessaires pour un composant frontend de diagramme de Gantt, en prenant en compte les dépendances entre OF.

---

## Module : 3D Vision

1.  **Extraction Automatique des Métadonnées** :
    *   **Contexte** : Le champ `metadata` est actuellement vide. On pourrait l'exploiter pour stocker des informations utiles directement depuis le fichier IFC.
    *   **Suggestion** : Créer un `ProjectModelObserver`. Sur l'événement `created`, déclencher un `Job` (`ProcessIfcMetadataJob`) qui utiliserait une librairie PHP pour extraire des informations du fichier IFC et les stocker dans le champ `metadata`.

2.  **Génération de Formats Web (Optimisation)** :
    *   **Contexte** : Les fichiers IFC sont très lourds pour le web.
    *   **Suggestion** : Dans le `ProcessIfcMetadataJob`, utiliser un convertisseur en ligne de commande (comme `IfcConvert`) pour générer une version `.glTF` du modèle, plus légère, et la stocker comme une conversion Spatie Media Library.

3.  **Nettoyage de la Relation** :
    *   **Contexte** : La relation `chantier()` dans `ProjectModel` a un argument redondant.
    *   **Suggestion** : Simplifier la définition de la relation de `belongsTo(Chantiers::class, 'chantiers_id')` à `belongsTo(Chantiers::class)`.
