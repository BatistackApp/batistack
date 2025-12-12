<?php

namespace App\Jobs\Comptabilite;

use App\Models\Comptabilite\ComptaEntry;
use App\Models\Comptabilite\ComptaRecurringEntryTemplate;
use DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PostRecurringEntriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
    }

    /**
     * @throws \Throwable
     */
    public function handle(): void
    {
        $today = Carbon::today()->toDateString();

        // 1. Récupérer tous les modèles d'abonnement dont la date d'enregistrement est atteinte.
        $templatesToPost = ComptaRecurringEntryTemplate::query()
            ->where('is_active', true)
            ->whereDate('next_posting_date', '<=', $today)
            ->get();

        if ($templatesToPost->isEmpty()) {
            return; // Aucune écriture à passer aujourd'hui.
        }

        DB::beginTransaction();
        try {
            foreach ($templatesToPost as $template) {
                // Créer l'écriture comptable (Débit)
                ComptaEntry::create([
                    'company_id' => $template->company_id,
                    'journal_id' => $template->journal_id,
                    'account_id' => $template->account_debit_id,
                    'date'       => Carbon::parse($today),
                    'label'      => "ABONNEMENT - {$template->name} (D)",
                    'debit'      => $template->amount,
                    'credit'     => 0,
                    // Référence pour lier les écritures entre elles et au template
                    'reference'  => "REC-{$template->id}-" . Carbon::now()->format('Ymd'),
                    'sourceable_type' => ComptaRecurringEntryTemplate::class,
                    'sourceable_id' => $template->id,
                ]);

                // Créer l'écriture comptable (Crédit) - Balance
                ComptaEntry::create([
                    'company_id' => $template->company_id,
                    'journal_id' => $template->journal_id,
                    'account_id' => $template->account_credit_id,
                    'date'       => Carbon::parse($today),
                    'label'      => "ABONNEMENT - {$template->name} (C)",
                    'debit'      => 0,
                    'credit'     => $template->amount,
                    'reference'  => "REC-{$template->id}-" . Carbon::now()->format('Ymd'),
                    'sourceable_type' => ComptaRecurringEntryTemplate::class,
                    'sourceable_id' => $template->id,
                ]);

                // Mettre à jour le modèle d'abonnement pour la prochaine exécution
                $template->update([
                    'last_posting_date' => Carbon::parse($today),
                    'next_posting_date' => $this->calculateNextDate($template),
                ]);
            }
            DB::commit();

            // Log ou Notification pour les administrateurs que les écritures ont été passées
            // Log::info("{$templatesToPost->count()} écritures d'abonnement passées avec succès.");

        } catch (\Throwable $e) {
            DB::rollBack();
            // Important : Utiliser la notification d'alerte comptable ici si elle existait
            // Ex: Admin::notify(new AccountingAlertNotification("Erreur lors de l'enregistrement des abonnements : " . $e->getMessage()));
            throw $e;
        }
    }

    /**
     * Calcule la date de la prochaine exécution en fonction de la périodicité.
     */
    protected function calculateNextDate(ComptaRecurringEntryTemplate $template): Carbon
    {
        $lastDate = $template->last_posting_date ?? Carbon::now();

        return match ($template->periodicity) {
            'quarterly' => $lastDate->addMonths(3),
            'yearly'    => $lastDate->addYear(),
            default     => $lastDate->addMonth(), // Par défaut, monthly
        };
    }
}
