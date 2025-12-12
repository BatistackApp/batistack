<?php

namespace App\Jobs\Comptabilite;

use App\Models\Comptabilite\ComptaEntry;
use App\Models\Core\Company;
use App\Models\User;
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
        // 1. Préparation du fichier
        // Format du nom FEC : SirenFECYYYYMMDD.txt (Norme DGFIP)
        $siren = $this->company->siren ?? '000000000';
        $dateCloture = str_replace('-', '', $this->fiscalYearEnd);
        $fileName = "{$siren}FEC{$dateCloture}.txt";
        $filePath = "exports/fec/{$this->company->id}/{$fileName}";

        // Création du Writer CSV (Tabulation separator pour le FEC standard)
        $csv = Writer::createFromString('');
        $csv->setDelimiter("\t"); // Séparateur Tabulation
        $csv->setEndOfLine("\r\n"); // Retour chariot Windows

        // 2. En-tête FEC (Norme A47)
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

        // 3. Récupération des écritures (Chunking pour la mémoire)
        ComptaEntry::query()
            ->where('company_id', $this->company->id)
            ->whereBetween('date', [$this->fiscalYearStart, $this->fiscalYearEnd])
            ->with(['journal', 'account'])
            ->orderBy('date')
            ->chunk(500, function ($entries) use ($csv) {
                foreach ($entries as $entry) {
                    $csv->insertOne([
                        $entry->journal->code,                      // JournalCode
                        $entry->journal->name,                      // JournalLib
                        $entry->reference ?? $entry->id,            // EcritureNum (Séquentiel ou Ref)
                        $entry->date->format('Ymd'),                // EcritureDate
                        $entry->account->number,                    // CompteNum
                        $entry->account->name,                      // CompteLib
                        '',                                         // CompAuxNum (Si auxiliaire, à gérer)
                        '',                                         // CompAuxLib
                        $entry->reference,                          // PieceRef
                        $entry->date->format('Ymd'),                // PieceDate
                        $entry->label,                              // EcritureLib
                        number_format($entry->debit, 2, ',', ''),   // Debit (Virgule française)
                        number_format($entry->credit, 2, ',', ''),  // Credit
                        $entry->lettrage,                           // EcritureLet
                        '',                                         // DateLet
                        $entry->created_at->format('Ymd'),          // ValidDate
                        '',                                         // Montantdevise
                        '',                                         // Idevise
                    ]);
                }
            });

        // 4. Sauvegarde
        Storage::put($filePath, $csv->toString());

        // 5. Notification
        $this->requestingUser->notify(new FecReadyNotification($filePath));
    }
}
