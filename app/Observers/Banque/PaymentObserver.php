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
