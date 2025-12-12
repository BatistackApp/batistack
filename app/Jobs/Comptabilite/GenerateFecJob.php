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

        ComptaEntry::query()
            ->where('company_id', $this->company->id)
            ->whereBetween('date', [$this->fiscalYearStart, $this->fiscalYearEnd])
            ->with(['journal', 'account', 'tier']) // Chargement de la relation tier
            ->orderBy('date')
            ->chunk(500, function ($entries) use ($csv) {
                foreach ($entries as $entry) {
                    $csv->insertOne([
                        $entry->journal->code,
                        $entry->journal->name,
                        $entry->reference ?? $entry->id,
                        $entry->date->format('Ymd'),
                        $entry->account->number,
                        $entry->account->name,
                        $entry->tier->id ?? '',          // CompAuxNum
                        $entry->tier->name ?? '',        // CompAuxLib
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
}
