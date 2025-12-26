<?php

namespace App\Observers\NoteFrais;

use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Enums\NoteFrais\ExpenseStatus;
use App\Models\Chantiers\Chantiers;
use App\Models\Facturation\SalesDocument;
use App\Models\NoteFrais\Expense;
use App\Models\User;
use App\Notifications\NoteFrais\ExpenseStatusUpdatedNotification;
use App\Notifications\NoteFrais\ExpenseSubmittedNotification;
use App\Services\Comptabilite\ExpenseComptaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ExpenseObserver
{

    public function saving(Expense $expense): void
    {
        // --- LOGIQUE DE CALCUL AUTOMATIQUE ---
        if ($expense->amount_ttc > 0 && $expense->vat_amount !== null && $expense->amount_ht == 0) {
            $expense->amount_ht = $expense->amount_ttc - $expense->vat_amount;
        }
        elseif ($expense->amount_ht > 0 && $expense->vat_amount !== null && $expense->amount_ttc == 0) {
            $expense->amount_ttc = $expense->amount_ht + $expense->vat_amount;
        }
        if ($expense->amount_ht < 0) $expense->amount_ht = 0;
    }

    /**
     * @throws \Throwable
     */
    public function saved(Expense $expense): void
    {
        // 1. Mise à jour du coût chantier
        if ($expense->isDirty('status', 'amount_ht', 'chantiers_id')) {
            $this->updateChantiersExpensesCost($expense->chantiers_id);

            if ($expense->wasChanged('chantiers_id')) {
                $this->updateChantiersExpensesCost($expense->getOriginal('chantiers_id'));
            }
        }

        // 2. Gestion des notifications et de la comptabilisation en fonction du statut
        if ($expense->wasChanged('status')) {
            switch ($expense->status) {
                case ExpenseStatus::Submitted:
                    if ($expense->getMedia('proof')->count() === 0) {
                        $expense->status = ExpenseStatus::Draft;
                        $expense->saveQuietly();
                        throw ValidationException::withMessages([
                           'proof' => 'Un justificatif est obligatoire pour soumettre la note de frais.',
                        ]);
                    }

                    $managers = User::where('company_id', $expense->company_id)
                        ->where('is_company_admin', true)
                        ->get();
                    Notification::send($managers, new ExpenseSubmittedNotification($expense));
                    break;

                case ExpenseStatus::Approved:
                    if ($expense->employee->user) {
                        $expense->employee->user->notify(new ExpenseStatusUpdatedNotification($expense));
                    }
                    // Comptabilisation
                    try {
                        app(ExpenseComptaService::class)->postExpenseEntry($expense);
                    } catch (\Exception $e) {
                        Log::error("Erreur de comptabilisation NDF {$expense->id}: " . $e->getMessage());
                    }
                    // Refacturation
                    if ($expense->is_billable) {
                        $this->billExpenseToClient($expense);
                    }
                    break;

                case ExpenseStatus::Rejected:
                    if ($expense->employee->user) {
                        $expense->employee->user->notify(new ExpenseStatusUpdatedNotification($expense));
                    }
                    break;
            }
        }
    }

    public function deleted(Expense $expense): void
    {
        $this->updateChantiersExpensesCost($expense->chantiers_id);
    }

    private function updateChantiersExpensesCost(?int $chantiersId): void
    {
        if (!$chantiersId) return;

        $total = Expense::where('chantiers_id', $chantiersId)
            ->whereIn('status', [ExpenseStatus::Approved, ExpenseStatus::Paid, ExpenseStatus::Posted])
            ->sum('amount_ht');

        Chantiers::where('id', $chantiersId)->update(['total_expenses_cost' => $total]);
    }

    private function billExpenseToClient(Expense $expense): void
    {
        if (!$expense->chantiers_id || !$expense->chantier->client_id) {
            return;
        }

        // On cherche une facture brouillon pour ce chantier, sinon on en crée une.
        $invoice = SalesDocument::firstOrCreate(
            [
                'company_id' => $expense->company_id,
                'chantiers_id' => $expense->chantiers_id,
                'type' => SalesDocumentType::Invoice,
                'status' => SalesDocumentStatus::Draft,
            ],
            [
                'tiers_id' => $expense->chantier->client_id,
                'date' => now(),
                'due_date' => now()->addDays(30),
            ]
        );

        // On ajoute la ligne de dépense à la facture.
        $invoice->lines()->create([
            'description' => "Refacturation NDF: {$expense->label}",
            'quantity' => 1,
            'unit_price' => $expense->amount_ttc, // On refacture le TTC
            'vat_rate' => 0, // La TVA est déjà incluse dans le prix
        ]);

        $invoice->recalculate();

        // On marque la dépense comme refacturée.
        $expense->update(['has_been_billed' => true]);
    }
}
