<?php

namespace App\Observers\Banque;

use App\Enums\Banque\PaymentStatus;
use App\Enums\Facturation\SalesDocumentStatus;
use App\Models\Banque\Payment;
use App\Models\Facturation\SalesDocument;
use App\Services\Comptabilite\PaymentComptaService;
use Illuminate\Support\Facades\Log;

class PaymentObserver
{
    public function saved(Payment $payment): void
    {
        if ($payment->payable_type === SalesDocument::class && $payment->payable_id) {
            $this->updateInvoiceStatus($payment->payable);
        }
    }

    /**
     * Gère la mise à jour du solde bancaire lorsque le statut de paiement change.
     */
    public function updated(Payment $payment): void
    {
        // Si le statut du paiement a changé
        if ($payment->isDirty('status')) {
            // Règle 1: Le paiement est rapproché ('cleared')
            if ($payment->status === PaymentStatus::Cleared && $payment->bank_account_id) {
                // Mise à jour du solde bancaire
                $amountToChange = $payment->is_incoming ? $payment->amount : -$payment->amount;
                $payment->bankAccount->updateBalance($amountToChange);

                // Comptabilisation du règlement
                try {
                    $comptaService = new PaymentComptaService();
                    $comptaService->postPaymentEntry($payment);
                    Log::info("Paiement #{$payment->id} comptabilisé avec succès.");
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la comptabilisation du paiement #{$payment->id}: " . $e->getMessage());
                }
            }
        }
    }

    public function deleted(Payment $payment): void
    {
        // Si on supprime un paiement, il faut remettre la facture à jour
        if ($payment->payable_type === SalesDocument::class && $payment->payable_id) {
            $this->updateInvoiceStatus($payment->payable);
        }

        // Si le paiement était lié à une transaction bancaire, on "libère" la transaction
        if ($payment->bank_transaction_id) {
            $payment->transaction()->update(['reconciled_at' => null]);
        }

        // Si le paiement est supprimé et qu'il avait été rapproché, on annule l'impact sur le solde.
        if ($payment->getOriginal('status') === PaymentStatus::Cleared && $payment->bank_account_id) {
            // Inverse l'opération faite lors du 'cleared'
            $amountToChange = $payment->is_incoming ? -$payment->amount : $payment->amount;
            $payment->bankAccount->updateBalance($amountToChange);

            // TODO: Contre-passer l'écriture comptable du paiement
        }
    }

    // Helper pour recalculer le statut
    private function updateInvoiceStatus(SalesDocument $invoice): void
    {
        // On recharge les paiements frais
        $totalPaid = $invoice->payments()->sum('amount');
        $totalDue = $invoice->total_ttc;

        // Tolérance de 1 centime pour les erreurs d'arrondi
        if ($totalPaid >= ($totalDue - 0.01)) {
            $status = SalesDocumentStatus::Paid;
        } elseif ($totalPaid > 0) {
            $status = SalesDocumentStatus::Partial;
        } else {
            // Si c'était envoyé, ça redevient envoyé. Sinon Draft ?
            // Disons Sent par défaut si elle a été validée.
            $status = SalesDocumentStatus::Sent;
        }

        // On update sans déclencher d'événements en boucle, sauf si nécessaire
        $invoice->updateQuietly(['status' => $status]);
    }
}
