<?php

namespace App\Console\Commands\Locations;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Enums\Facturation\PurchaseDocumentType;
use App\Enums\Locations\RentalContractStatus;
use App\Enums\Locations\RentalPeriodicity;
use App\Models\Facturation\PurchaseDocument;
use App\Models\Locations\RentalContract;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GenerateRentalSupplierInvoicesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locations:generate-invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère automatiquement les factures fournisseurs pour les contrats de location actifs arrivés à échéance de facturation.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Début de la génération des factures fournisseurs de location...');

        $contracts = RentalContract::query()
            ->where('status', RentalContractStatus::Active)
            ->whereNotNull('next_billing_date')
            ->where('next_billing_date', '<=', now())
            ->get();

        $count = 0;

        foreach ($contracts as $contract) {
            DB::beginTransaction();
            try {
                // 1. Créer le document d'achat (Facture Fournisseur)
                $purchaseDoc = PurchaseDocument::create([
                    'company_id' => $contract->company_id,
                    'tiers_id' => $contract->tiers_id,
                    'chantiers_id' => $contract->chantiers_id, // Imputation analytique au chantier
                    'type' => PurchaseDocumentType::Invoice,
                    'status' => PurchaseDocumentStatus::Draft, // On laisse en brouillon pour validation
                    'date' => now(),
                    'due_date' => now()->addDays(30), // TODO: Configurable par fournisseur
                    'reference' => "LOC-{$contract->id}-" . now()->format('Ymd'),
                    'sourceable_type' => RentalContract::class,
                    'sourceable_id' => $contract->id,
                ]);

                // 2. Créer les lignes de facture basées sur les lignes du contrat
                foreach ($contract->lines as $line) {
                    $purchaseDoc->lines()->create([
                        'description' => $line->description . " (Période du " . $contract->next_billing_date->copy()->sub($this->getInterval($contract->periodicity))->format('d/m/Y') . " au " . $contract->next_billing_date->format('d/m/Y') . ")",
                        'quantity' => $line->quantity,
                        'unit_price' => $line->unit_price,
                        'vat_rate' => $line->vat_rate,
                    ]);
                }

                $purchaseDoc->recalculate();

                // 3. Mettre à jour la prochaine date de facturation
                $nextDate = $this->calculateNextBillingDate($contract->next_billing_date, $contract->periodicity);

                // Si la prochaine date dépasse la date de fin du contrat, on met la date de fin ou null
                if ($contract->end_date && $nextDate > $contract->end_date) {
                    // Si on veut facturer le prorata, c'est ici que ça se complexifie.
                    // Pour l'instant, on arrête la facturation récurrente.
                    $contract->update(['next_billing_date' => null]);
                } else {
                    $contract->update(['next_billing_date' => $nextDate]);
                }

                DB::commit();
                $count++;
                $this->info("Facture générée pour le contrat #{$contract->id}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Erreur lors de la génération de facture pour le contrat location #{$contract->id}: " . $e->getMessage());
                $this->error("Erreur pour le contrat #{$contract->id}");
            }
        }

        $this->info("Terminé. {$count} factures générées.");
    }

    private function getInterval(RentalPeriodicity $periodicity): \DateInterval
    {
        return match ($periodicity) {
            RentalPeriodicity::Daily => new \DateInterval('P1D'),
            RentalPeriodicity::Weekly => new \DateInterval('P1W'),
            RentalPeriodicity::Monthly => new \DateInterval('P1M'),
        };
    }

    private function calculateNextBillingDate(\DateTimeInterface $currentDate, RentalPeriodicity $periodicity): \DateTimeInterface
    {
        $date = \Carbon\Carbon::instance($currentDate);
        return match ($periodicity) {
            RentalPeriodicity::Daily => $date->addDay(),
            RentalPeriodicity::Weekly => $date->addWeek(),
            RentalPeriodicity::Monthly => $date->addMonth(),
        };
    }
}
