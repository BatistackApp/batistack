<?php

namespace App\Observers\NoteFrais;

use App\Enums\NoteFrais\ExpenseStatus;
use App\Models\Chantiers\Chantiers;
use App\Models\NoteFrais\Expense;
use App\Models\User;
use App\Notifications\NoteFrais\ExpenseStatusUpdatedNotification;
use App\Notifications\NoteFrais\ExpenseSubmittedNotification;
use App\Services\Comptabilite\ExpenseComptaService;
use Illuminate\Support\Facades\Notification;

class ExpenseObserver
{

    public function saving(Expense $expense): void
    {
        // --- LOGIQUE DE CALCUL AUTOMATIQUE ---

        // Cas 1 : L'utilisateur a saisi TTC et TVA (Cas le plus fréquent)
        // On déduit le HT : 120€ TTC - 20€ TVA = 100€ HT
        if ($expense->amount_ttc > 0 && $expense->vat_amount !== null && $expense->amount_ht == 0) {
            $expense->amount_ht = $expense->amount_ttc - $expense->vat_amount;
        }

        // Cas 2 : L'utilisateur a saisi HT et TVA (Cas facture fournisseur)
        // On calcule le TTC : 100€ HT + 20€ TVA = 120€ TTC
        elseif ($expense->amount_ht > 0 && $expense->vat_amount !== null && $expense->amount_ttc == 0) {
            $expense->amount_ttc = $expense->amount_ht + $expense->vat_amount;
        }

        // Cas 3 : Sécurité anti-négatifs
        if ($expense->amount_ht < 0) $expense->amount_ht = 0;

        // --- SUIVI DES COÛTS ---
        // (Identique à avant, on gère les changements de statut)
    }
    public function saved(Expense $expense): void
    {

        // 2. Mise à jour du coût chantier
        // On le fait si le statut change (ex: Submitted -> Approved)
        if ($expense->isDirty('status') || $expense->isDirty('amount_ht') || $expense->isDirty('chantiers_id')) {
            $this->updateChantiersExpensesCost($expense->chantiers_id);

            // Si on change de projet, update l'ancien
            if ($expense->getOriginal('project_id')) {
                $this->updateChantiersExpensesCost($expense->getOriginal('chantiers_id'));
            }
        }

        // NOTIFICATION 1 : Soumission (Draft -> Submitted)
        if ($expense->isDirty('status') && $expense->status === ExpenseStatus::Submitted) {
            // Trouver le manager (Ou les admins du tenant)
            // Simplification : on notifie tous les admins de la company
            $managers = User::where('company_id', $expense->company_id)
                ->where('is_company_admin', true) // Supposons un flag admin
                ->get();

            Notification::send($managers, new ExpenseSubmittedNotification($expense));
        }

        // NOTIFICATION 2 : Décision (Submitted -> Approved/Rejected)
        if ($expense->isDirty('status') && in_array($expense->status, [ExpenseStatus::Approved, ExpenseStatus::Rejected])) {
            // On notifie l'employé s'il a un compte utilisateur
            if ($expense->employee->user) {
                $expense->employee->user->notify(new ExpenseStatusUpdatedNotification($expense));
            }
        }
    }

    /**
     * @throws \Throwable
     */
    public function updated(Expense $expense): void
    {
        // 1. Détection de la validation : Est-ce que le champ de validation a changé
        //    ET est-ce qu'il est maintenant à TRUE ?
        if ($expense->wasChanged('is_validated') && $expense->is_validated === true) {

            // 2. Vérification de la comptabilisation : S'assurer qu'elle n'est pas déjà comptabilisée.
            if (!$expense->is_posted) {

                // 3. Appel du service de comptabilisation
                /** @var ExpenseComptaService $service */
                $service = app(ExpenseComptaService::class);

                // On pourrait utiliser un Job pour déporter la charge du traitement :
                // PostExpenseEntryJob::dispatch($expense);
                // Mais pour une logique simple d'Observer, l'appel direct est suffisant.
                $service->postExpenseEntry($expense);
            }
        }
    }

    public function deleted(Expense $expense): void
    {
        $this->updateChantiersExpensesCost($expense->chantiers_id);
    }

    // Recalcul du coût des frais sur le chantier
    private function updateChantiersExpensesCost(?int $chantiersId): void
    {
        if (!$chantiersId) return;

        // On ne compte que les frais VALIDÉS ou PAYÉS dans le coût réel
        $total = Expense::where('chantiers_id', $chantiersId)
            ->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Paid])
            ->sum('amount_ht'); // Le coût pour l'entreprise est le HT (si on récupère la TVA)

        Chantiers::where('id', $chantiersId)->update(['total_expenses_cost' => $total]);
    }
}
