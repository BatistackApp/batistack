<?php

namespace App\Jobs\Comptabilite;

use App\Models\Comptabilite\ComptaEntry;
use App\Models\Core\Company;
use App\Models\User;
use App\Notifications\Comptabilite\FecErrorNotification;
use App\Notifications\Comptabilite\FecReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Writer;
use Storage;

class GenerateFecJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Company $company,
        public string $fiscalYearStart,
        public string $fiscalYearEnd,
        public User $requestingUser
    ){}

    /**
     * @throws InvalidArgument
     * @throws Exception
     */
    public function handle(): void
    {
        // 1. Validation préalable
        $errors = $this->validateEntries();
        if (!empty($errors)) {
            // On notifie l'utilisateur des erreurs et on arrête le job
            // Supposons qu'on a une notification FecErrorNotification
            // Si elle n'existe pas, je devrais la créer, mais pour l'instant je vais utiliser un log ou une notif générique si possible.
            // Je vais supposer que je peux créer cette notification ou utiliser une méthode simple.
            // Pour rester simple, je vais utiliser une notification Filament standard si possible, mais ici on est dans un Job.
            // Je vais utiliser FecReadyNotification avec un flag d'erreur ou créer une nouvelle classe.
            // Pour l'instant, je vais simuler l'envoi d'erreur via FecReadyNotification en changeant le message, ou mieux, créer la classe manquante.

            // Je vais créer la notification d'erreur juste après.
             $this->requestingUser->notify(new FecErrorNotification($errors));
             return;
        }

        $siren = $this->company->siren ?? '000000000';
        $dateCloture = str_replace('-', '', $this->fiscalYearEnd);
        $fileName = "{$siren}FEC{$dateCloture}.txt";
        $filePath = "exports/fec/{$this->company->id}/{$fileName}";

        $csv = Writer::createFromString('');
        $csv->setDelimiter("\t");
        $csv->setEndOfLine("\r\n");

        $headers = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
            'PieceRef', 'PieceDate', 'EcritureLib',
            'Debit', 'Credit', 'EcritureLet', 'DateLet', 'ValidDate',
            'Montantdevise', 'Idevise'
        ];
        try {
            $csv->insertOne($headers);
        } catch (CannotInsertRecord|Exception $e) {
            \Log::emergency($e->getMessage(), ['file' => $e->getFile(), 'line' => $e->getLine()]);
        }

        // Initialisation des compteurs pour EcritureNum
        $entryCounters = [];

        ComptaEntry::query()
            ->where('company_id', $this->company->id)
            ->whereBetween('date', [$this->fiscalYearStart, $this->fiscalYearEnd])
            ->with(['journal', 'account', 'tier'])
            ->orderBy('journal_id')
            ->orderBy('date')
            ->orderBy('id')
            ->chunk(500, function ($entries) use ($csv, &$entryCounters) {
                foreach ($entries as $entry) {
                    $counterKey = $entry->journal->code;

                    if (!isset($entryCounters[$counterKey])) {
                        $entryCounters[$counterKey] = 1;
                    }

                    $ecritureNum = $entryCounters[$counterKey]++;

                    $csv->insertOne([
                        $entry->journal->code,
                        $entry->journal->name,
                        $ecritureNum,
                        $entry->date->format('Ymd'),
                        $entry->account->number,
                        $entry->account->name,
                        $entry->tier->id ?? '',
                        $entry->tier->name ?? '',
                        $entry->reference,
                        $entry->date->format('Ymd'),
                        $entry->label,
                        number_format($entry->debit, 2, ',', ''),
                        number_format($entry->credit, 2, ',', ''),
                        $entry->lettrage,
                        '',
                        $entry->created_at->format('Ymd'),
                        '',
                        '',
                    ]);
                }
            });

        Storage::put($filePath, $csv->toString());

        $this->requestingUser->notify(new FecReadyNotification($filePath));
    }

    protected function validateEntries(): array
    {
        $errors = [];

        $query = ComptaEntry::query()
            ->where('company_id', $this->company->id)
            ->whereBetween('date', [$this->fiscalYearStart, $this->fiscalYearEnd]);

        // 1. Vérification de l'équilibre global
        $totalDebit = (clone $query)->sum('debit');
        $totalCredit = (clone $query)->sum('credit');

        if (abs($totalDebit - $totalCredit) > 0.01) {
            $errors[] = "Les écritures ne sont pas équilibrées. Total Débit: {$totalDebit}, Total Crédit: {$totalCredit}. Écart: " . ($totalDebit - $totalCredit);
        }

        // 2. Vérification des comptes manquants
        $missingAccounts = (clone $query)->whereNull('account_id')->count();
        if ($missingAccounts > 0) {
            $errors[] = "Il y a {$missingAccounts} écritures sans compte comptable associé.";
        }

        // 3. Vérification des journaux manquants
        $missingJournals = (clone $query)->whereNull('journal_id')->count();
        if ($missingJournals > 0) {
            $errors[] = "Il y a {$missingJournals} écritures sans journal associé.";
        }

        return $errors;
    }
}
