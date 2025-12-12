<?php

namespace App\Observers\Banque;

use App\Enums\Facturation\SalesDocumentStatus;
use App\Models\Banque\Payment;
use App\Models\Facturation\SalesDocument;

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
        // Règle 1: Mise à jour du Solde Bancaire
        // On ne met à jour le solde que si :
        // 1. Le statut est passé à 'cleared' (encaissé/décaissé)
        // 2. Un compte bancaire est affecté
        // 3. Le statut POUVAIT être autre chose qu'un rapprochement déjà fait
        if ($payment->isDirty('status') && $payment->status === 'cleared' && $payment->bank_account_id) {

            // Le montant à ajouter/soustraire dépend si c'est un encaissement ou un décaissement.
            // Le modèle Payment a une méthode 'getIsIncomingAttribute'
            $amountToChange = $payment->is_incoming
                ? $payment->amount // Encaissement (ajouter)
                : -$payment->amount; // Décaissement (soustraire)

            // On met à jour le solde du compte
            $payment->bankAccount->updateBalance($amountToChange);
        }

        // Règle 2: Mise à jour du statut de la Facture/Achat
        // Si le statut passe à 'cleared', on met à jour le statut du document lié.
        if ($payment->isDirty('status') && $payment->status === 'cleared' && $payment->payable) {
            // Exemple : Mettre à jour l'entité payable (Facture/Achat) pour dire qu'elle est payée.
            // $payment->payable->checkPaymentStatus(); // Logique complexe de statut de document
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
        if ($payment->status === 'cleared' && $payment->bank_account_id) {
            // Inverse l'opération faite lors du 'cleared'
            $amountToChange = $payment->is_incoming
                ? -$payment->amount // Encaissement -> on soustrait
                : $payment->amount; // Décaissement -> on ajoute

            $payment->bankAccount->updateBalance($amountToChange);
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
