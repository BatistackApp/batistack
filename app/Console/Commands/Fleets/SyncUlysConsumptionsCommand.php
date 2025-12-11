<?php

namespace App\Console\Commands\Fleets;

use App\Models\Fleets\Fleet;
use App\Models\Fleets\UlysConsumption;
use App\Services\Fleets\UlysService;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Log;

class SyncUlysConsumptionsCommand extends Command
{
    protected $signature = 'fleet:sync-ulys-consumptions';

    protected $description = 'Synchronise les consommations de télépéage Ulys pour les flottes configurées.';

    public function handle(UlysService $ulysService): int
    {
        $this->info('Démarrage de la synchronisation Ulys...');
        // 1. Déterminer la période de synchronisation
        $days = config('ulys.retrieval_days', 7);
        $endDate = Carbon::now()->format('Y-m-d');
        // On va un jour de plus dans le passé par sécurité, puis on utilise le DISTINCT ID Ulys pour éviter les doublons
        $startDate = Carbon::now()->subDays($days + 1)->format('Y-m-d');

        $this->comment("Période de synchro: Du {$startDate} au {$endDate} ({$days} jours).");

        // 2. Authentification Ulys
        if (!$ulysService->authenticate()) {
            $this->error('Échec de l\'authentification Ulys. Vérifiez les identifiants de configuration.');
            return Command::FAILURE;
        }

        // --- PARTIE SPÉCIFIQUE ULYS SANDBOX ---
        // Selon la documentation (Vider la mémoire entre chaque acte de gestion),
        // on appelle clearSandboxMemory si on est en environnement 'sandbox'.
        if (config('ulys.env') === 'sandbox') {
            $ulysService->clearSandboxMemory(); // Tente de nettoyer avant l'appel principal
            $this->warn('Nettoyage de la mémoire Sandbox Ulys effectué (si réussi).');
        }

        // 3. Récupérer les consommations
        $ulysTransactions = $ulysService->getConsumptions($startDate, $endDate);

        if (empty($ulysTransactions)) {
            $this->warn('Aucune transaction Ulys récupérée sur cette période ou erreur API.');
            return Command::SUCCESS;
        }

        $this->info("Récupération de " . count($ulysTransactions) . " transactions Ulys.");

        // 4. Mapper les transactions aux véhicules
        // On récupère en une seule requête tous les IDs Ulys de notre flotte pour un mapping rapide
        $ulysIdsToFleet = Fleet::whereNotNull('ulys_badge_id')
            ->pluck('id', 'ulys_badge_id') // ['ID_ULYS' => FLEET_ID]
            ->toArray();

        $insertedCount = 0;

        DB::transaction(function () use ($ulysTransactions, $ulysIdsToFleet, &$insertedCount) {
            foreach ($ulysTransactions as $transaction) {
                $badgeId = $transaction['badgeId'] ?? null;
                $companyId = $transaction['companyId'] ?? null; // Si Ulys fournit l'ID de compagnie

                // Trouver le véhicule correspondant dans notre flotte
                $fleetId = $ulysIdsToFleet[$badgeId] ?? null;

                if (!$fleetId) {
                    $this->warn("Transaction ignorée: Badge ID {$badgeId} non associé à un véhicule Batistack.");
                    continue; // On passe à la transaction suivante
                }

                // 5. Créer ou Mettre à jour (si l'ID transactionnel est unique)
                UlysConsumption::updateOrCreate(
                    [
                        // Clé unique selon la documentation Ulys : transactionId
                        'ulys_transaction_id' => $transaction['transactionId'],
                    ],
                    [
                        'company_id' => $companyId ?? Fleet::find($fleetId)->company_id, // Récupération via le modèle si non fourni par Ulys
                        'fleet_id' => $fleetId,
                        'badge_id' => $badgeId,
                        'transaction_date' => Carbon::parse($transaction['transactionDate'] . ' ' . $transaction['transactionTime']),
                        'amount' => $transaction['totalPrice'],
                        'currency' => $transaction['currency'] ?? 'EUR',
                        'toll_station' => $transaction['exitStationName'] ?? null,
                        'entry_station' => $transaction['entryStationName'] ?? null,
                        'exit_station' => $transaction['exitStationName'] ?? null,
                        'raw_data' => $transaction, // Stocker toutes les données pour la référence
                    ]
                );
                $insertedCount++;
            }
        });

        $this->info("Synchronisation Ulys terminée. {$insertedCount} transactions traitées.");
        Log::info('Synchronisation Ulys terminée.', ['count' => $insertedCount]);

        return Command::SUCCESS;
    }
}
