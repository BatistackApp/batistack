<?php

namespace App\Console\Commands\Comptabilite;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Models\Core\Company;
use App\Models\Facturation\PurchaseDocument;
use App\Models\Facturation\SalesDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Spatie\Browsershot\Browsershot;

class GenerateAgedBalanceCommand extends Command
{
    protected $signature = 'compta:generate-aged-balance {--as-of-date=}';
    protected $description = 'Generates Aged Receivable and Aged Payable reports (PDF and CSV).';

    public function handle()
    {
        $asOfDate = $this->option('as-of-date') ? Carbon::parse($this->option('as-of-date')) : Carbon::today();
        $this->info("Generating aged balance reports as of {$asOfDate->format('d/m/Y')}...");

        $company = Company::first(); // Pour l'instant, on traite la première compagnie
        if (!$company) {
            $this->error('No company found.');
            return 1;
        }

        $this->generateAgedReceivableReport($company, $asOfDate);
        $this->generateAgedPayableReport($company, $asOfDate);

        $this->info("\nAged balance reports generated successfully.");
        return 0;
    }

    private function generateAgedReceivableReport(Company $company, Carbon $asOfDate): void
    {
        $this->line('Processing Aged Receivable...');
        $unpaidInvoices = SalesDocument::where('company_id', $company->id)
            ->where('type', SalesDocumentType::Invoice)
            ->whereIn('status', [SalesDocumentStatus::Sent, SalesDocumentStatus::Partial, SalesDocumentStatus::Overdue])
            ->with('tiers')
            ->get();

        if ($unpaidInvoices->isEmpty()) {
            $this->info('No unpaid sales invoices found.');
            return;
        }

        $reportData = $this->calculateBuckets($unpaidInvoices, $asOfDate);
        $this->generatePdf($reportData, 'Receivable', 'Clients', $company, $asOfDate);
        $this->generateCsv($unpaidInvoices, 'Receivable', 'Clients', $company, $asOfDate);
    }

    private function generateAgedPayableReport(Company $company, Carbon $asOfDate): void
    {
        $this->line('Processing Aged Payable...');
        $unpaidPurchases = PurchaseDocument::where('company_id', $company->id)
            ->whereIn('status', [PurchaseDocumentStatus::Approved, PurchaseDocumentStatus::Partial])
            ->with('tiers')
            ->get();

        if ($unpaidPurchases->isEmpty()) {
            $this->info('No unpaid purchase invoices found.');
            return;
        }

        $reportData = $this->calculateBuckets($unpaidPurchases, $asOfDate);
        $this->generatePdf($reportData, 'Payable', 'Fournisseurs', $company, $asOfDate);
        $this->generateCsv($unpaidPurchases, 'Payable', 'Fournisseurs', $company, $asOfDate);
    }

    private function calculateBuckets($invoices, Carbon $asOfDate): array
    {
        $buckets = [];
        $totals = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, '90+' => 0, 'total' => 0];

        foreach ($invoices as $invoice) {
            $tierName = $invoice->tiers->name;
            $dueDate = $invoice->due_date;
            // TODO: Utiliser le solde restant au lieu du total TTC si les paiements partiels sont gérés
            $amount = $invoice->total_ttc;

            if (!isset($buckets[$tierName])) {
                $buckets[$tierName] = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, '90+' => 0, 'total' => 0];
            }

            $daysOverdue = $asOfDate->diffInDays($dueDate, false);

            $bucket = match (true) {
                $daysOverdue >= 0 => 'current',
                $daysOverdue >= -30 => '30',
                $daysOverdue >= -60 => '60',
                $daysOverdue >= -90 => '90',
                default => '90+',
            };

            $buckets[$tierName][$bucket] += $amount;
            $buckets[$tierName]['total'] += $amount;
            $totals[$bucket] += $amount;
            $totals['total'] += $amount;
        }
        $buckets['Totals'] = $totals;
        return $buckets;
    }

    private function generatePdf(array $data, string $type, string $tierType, Company $company, Carbon $asOfDate): void
    {
        $html = $this->getPdfHtml($data, $type, $tierType, $asOfDate);
        $path = "reports/compta/{$company->id}/aged_balance/aged_{$type}_" . $asOfDate->format('Y-m-d') . ".pdf";
        Browsershot::html($html)->setPaperSize('a4', 'landscape')->save(Storage::path($path));
    }

    private function generateCsv($invoices, string $type, string $tierType, Company $company, Carbon $asOfDate): void
    {
        $path = "reports/compta/{$company->id}/aged_balance/detailed_aged_{$type}_" . $asOfDate->format('Y-m-d') . ".csv";
        $csv = Writer::createFromString('');
        $csv->setDelimiter(';');
        $csv->insertOne([$tierType, 'Référence Facture', 'Date Facture', 'Date Échéance', 'Jours de Retard', 'Montant Dû', 'Catégorie']);

        foreach ($invoices as $invoice) {
            $daysOverdue = $asOfDate->diffInDays($invoice->due_date, false);
            $category = match (true) {
                $daysOverdue >= 0 => 'Courant',
                $daysOverdue >= -30 => '1-30 jours',
                $daysOverdue >= -60 => '31-60 jours',
                $daysOverdue >= -90 => '61-90 jours',
                default => '90+ jours',
            };
            $csv->insertOne([
                $invoice->tiers->name,
                $invoice->reference,
                $invoice->document_date->format('d/m/Y'),
                $invoice->due_date->format('d/m/Y'),
                max(0, $daysOverdue * -1),
                number_format($invoice->total_ttc, 2, ',', ' '),
                $category,
            ]);
        }
        Storage::put($path, $csv->toString());
    }

    private function getPdfHtml(array $data, string $type, string $tierType, Carbon $asOfDate): string
    {
        $formatNumber = fn($num) => number_format($num, 2, ',', ' ') . ' €';
        $title = $type === 'Receivable' ? 'Balance Âgée Clients' : 'Balance Âgée Fournisseurs';
        $totals = $data['Totals'];
        unset($data['Totals']);

        $rows = '';
        foreach ($data as $tierName => $values) {
            $rows .= "<tr>
                <th>{$tierName}</th>
                <td>{$formatNumber($values['current'])}</td>
                <td>{$formatNumber($values['30'])}</td>
                <td>{$formatNumber($values['60'])}</td>
                <td>{$formatNumber($values['90'])}</td>
                <td>{$formatNumber($values['90+'])}</td>
                <td class='total'>{$formatNumber($values['total'])}</td>
            </tr>";
        }

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>{$title}</title>
            <style>
                body { font-family: sans-serif; font-size: 10px; }
                h1 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ccc; padding: 6px; text-align: right; }
                th { background-color: #f2f2f2; text-align: left; }
                .total { font-weight: bold; border-left: 2px solid #333; }
                tfoot tr { background-color: #f2f2f2; font-weight: bold; }
            </style>
        </head>
        <body>
            <h1>{$title}</h1>
            <p><strong>Date du rapport :</strong> {$asOfDate->format('d/m/Y')}</p>
            <table>
                <thead>
                    <tr>
                        <th>{$tierType}</th>
                        <th>Courant</th>
                        <th>1-30 jours</th>
                        <th>31-60 jours</th>
                        <th>61-90 jours</th>
                        <th>90+ jours</th>
                        <th class="total">Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
                <tfoot>
                    <tr>
                        <th>TOTAL GÉNÉRAL</th>
                        <td>{$formatNumber($totals['current'])}</td>
                        <td>{$formatNumber($totals['30'])}</td>
                        <td>{$formatNumber($totals['60'])}</td>
                        <td>{$formatNumber($totals['90'])}</td>
                        <td>{$formatNumber($totals['90+'])}</td>
                        <td class="total">{$formatNumber($totals['total'])}</td>
                    </tr>
                </tfoot>
            </table>
        </body>
        </html>
        HTML;
    }
}
