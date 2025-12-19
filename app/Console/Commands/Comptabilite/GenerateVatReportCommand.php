<?php

namespace App\Console\Commands\Comptabilite;

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaEntry;
use App\Models\Core\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Spatie\Browsershot\Browsershot;

class GenerateVatReportCommand extends Command
{
    protected $signature = 'compta:generate-vat-report {--month=} {--year=}';
    protected $description = 'Generates a VAT report (PDF and CSV) for a given period.';

    public function handle()
    {
        $month = $this->option('month') ?? now()->month;
        $year = $this->option('year') ?? now()->year;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $this->info("Generating VAT report for {$startDate->format('F Y')}...");

        $company = Company::first();
        if (!$company) {
            $this->error('No company found.');
            return 1;
        }

        // --- Récupération des données ---
        // TODO: Rendre les numéros de compte configurables
        $collectedVatAccountNumbers = ['445710'];
        $deductibleVatAccountNumbers = ['445660'];

        $collectedVatEntries = ComptaEntry::where('company_id', $company->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('account', fn ($q) => $q->whereIn('number', $collectedVatAccountNumbers))
            ->get();

        $deductibleVatEntries = ComptaEntry::where('company_id', $company->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereHas('account', fn ($q) => $q->whereIn('number', $deductibleVatAccountNumbers))
            ->get();

        $totalCollectedVat = $collectedVatEntries->sum('credit');
        $totalDeductibleVat = $deductibleVatEntries->sum('debit');
        $vatToPay = $totalCollectedVat - $totalDeductibleVat;

        // --- Génération des rapports ---
        $this->generatePdfReport($company, $startDate, $totalCollectedVat, $totalDeductibleVat, $vatToPay);
        $this->generateCsvReport($company, $startDate, $collectedVatEntries, $deductibleVatEntries);

        $this->info("\nVAT report generated successfully.");
        return 0;
    }

    private function generatePdfReport(Company $company, Carbon $startDate, float $totalCollected, float $totalDeductible, float $vatToPay): void
    {
        $html = $this->getPdfHtml($startDate, $totalCollected, $totalDeductible, $vatToPay);
        $path = "reports/compta/{$company->id}/vat/vat_report_" . $startDate->format('Y-m') . ".pdf";
        Browsershot::html($html)->setPaperSize('a4')->save(Storage::path($path));
    }

    private function generateCsvReport(Company $company, Carbon $startDate, $collectedVatEntries, $deductibleVatEntries): void
    {
        $path = "reports/compta/{$company->id}/vat/detailed_vat_report_" . $startDate->format('Y-m') . ".csv";
        $csv = Writer::createFromString('');
        $csv->setDelimiter(';');

        $csv->insertOne(['Type TVA', 'Date', 'Journal', 'Libellé', 'Montant']);
        $csv->insertOne([]);

        $csv->insertOne(['TVA COLLECTÉE']);
        foreach ($collectedVatEntries as $entry) {
            $csv->insertOne(['Collectée', $entry->date->format('d/m/Y'), $entry->journal->code, $entry->label, number_format($entry->credit, 2, ',', ' ')]);
        }
        $csv->insertOne([]);

        $csv->insertOne(['TVA DÉDUCTIBLE']);
        foreach ($deductibleVatEntries as $entry) {
            $csv->insertOne(['Déductible', $entry->date->format('d/m/Y'), $entry->journal->code, $entry->label, number_format($entry->debit, 2, ',', ' ')]);
        }

        Storage::put($path, $csv->toString());
    }

    private function getPdfHtml(Carbon $startDate, float $totalCollected, float $totalDeductible, float $vatToPay): string
    {
        $formatNumber = fn($num) => number_format($num, 2, ',', ' ') . ' €';
        $vatToPayLabel = $vatToPay >= 0 ? 'TVA à décaisser' : 'Crédit de TVA';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Rapport de TVA - {$startDate->format('F Y')}</title>
            <style>
                body { font-family: sans-serif; font-size: 12px; }
                h1 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; }
                th { background-color: #f2f2f2; text-align: left; }
                .total { font-weight: bold; font-size: 14px; }
            </style>
        </head>
        <body>
            <h1>Rapport de TVA</h1>
            <h2>Période : {$startDate->format('F Y')}</h2>
            <table>
                <tr>
                    <th>TVA Collectée</th>
                    <td style="text-align: right;">{$formatNumber($totalCollected)}</td>
                </tr>
                <tr>
                    <th>TVA Déductible</th>
                    <td style="text-align: right;">{$formatNumber($totalDeductible)}</td>
                </tr>
                <tr class="total">
                    <th>{$vatToPayLabel}</th>
                    <td style="text-align: right;">{$formatNumber(abs($vatToPay))}</td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }
}
