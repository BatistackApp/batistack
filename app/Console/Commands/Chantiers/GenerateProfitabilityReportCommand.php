<?php

namespace App\Console\Commands\Chantiers;

use App\Models\Chantiers\Chantiers;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Spatie\Browsershot\Browsershot;

class GenerateProfitabilityReportCommand extends Command
{
    protected $signature = 'chantiers:generate-profitability-report {chantier_id?} {--all}';
    protected $description = 'Generates a profitability report (PDF and CSV) for one or all chantiers.';

    public function handle()
    {
        $chantierId = $this->argument('chantier_id');
        $all = $this->option('all');

        if (!$chantierId && !$all) {
            $this->error('Please provide a chantier_id or use the --all option.');
            return 1;
        }

        $chantiers = $all ? Chantiers::all() : Chantiers::where('id', $chantierId)->get();

        if ($chantiers->isEmpty()) {
            $this->info('No chantiers found.');
            return 0;
        }

        $this->info("Generating reports for {$chantiers->count()} chantier(s)...");

        $bar = $this->output->createProgressBar($chantiers->count());
        $bar->start();

        foreach ($chantiers as $chantier) {
            $this->generatePdfReport($chantier);
            $this->generateCsvReport($chantier);
            $bar->advance();
        }

        $bar->finish();
        $this->info("\nReports generated successfully.");
        return 0;
    }

    private function generatePdfReport(Chantiers $chantier): void
    {
        $html = $this->getPdfHtml($chantier);
        $path = "reports/chantiers/{$chantier->id}/profitability_report_" . now()->format('Y-m-d') . ".pdf";

        Browsershot::html($html)
            ->setPaperSize('a4')
            ->save(Storage::path($path));
    }

    private function generateCsvReport(Chantiers $chantier): void
    {
        $path = "reports/chantiers/{$chantier->id}/profitability_report_" . now()->format('Y-m-d') . ".csv";
        $csv = Writer::createFromString('');
        $csv->setDelimiter(';');

        $csv->insertOne(['Catégorie', 'Description', 'Budgété', 'Réel', 'Écart']);
        $csv->insertOne([]);

        // Revenus
        $csv->insertOne(['Revenus', 'Total Ventes', $chantier->budgeted_revenue, $chantier->total_sales_revenue, $chantier->total_sales_revenue - $chantier->budgeted_revenue]);
        $csv->insertOne([]);

        // Coûts
        $csv->insertOne(['Coûts', 'Main d\'œuvre', $chantier->budgeted_labor_cost, $chantier->total_labor_cost, $chantier->total_labor_cost - $chantier->budgeted_labor_cost]);
        $csv->insertOne(['Coûts', 'Achats', 0, $chantier->total_purchase_cost, $chantier->total_purchase_cost]);
        $csv->insertOne(['Coûts', 'Matériaux (OF)', $chantier->budgeted_material_cost, $chantier->total_material_cost, $chantier->total_material_cost - $chantier->budgeted_material_cost]);
        $csv->insertOne(['Coûts', 'Location', $chantier->budgeted_rental_cost, $chantier->total_rental_cost, $chantier->total_rental_cost - $chantier->budgeted_rental_cost]);
        $csv->insertOne(['Coûts', 'TOTAL COÛTS', $chantier->total_budgeted_cost, $chantier->total_real_cost, $chantier->total_real_cost - $chantier->total_budgeted_cost]);
        $csv->insertOne([]);

        // Marges
        $csv->insertOne(['Marge', 'Marge Brute', $chantier->budgeted_margin, $chantier->real_margin, $chantier->margin_difference]);

        Storage::put($path, $csv->toString());
    }

    private function getPdfHtml(Chantiers $chantier): string
    {
        $formatNumber = fn($num) => number_format($num, 2, ',', ' ') . ' €';
        $formatColor = fn($num) => $num >= 0 ? 'color: green;' : 'color: red;';

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Rapport de Rentabilité - {$chantier->name}</title>
            <style>
                body { font-family: sans-serif; font-size: 12px; }
                h1, h2 { text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ccc; padding: 8px; text-align: right; }
                th { background-color: #f2f2f2; text-align: left; }
                .total { font-weight: bold; }
            </style>
        </head>
        <body>
            <h1>Rapport de Rentabilité</h1>
            <h2>Chantier : {$chantier->name}</h2>
            <p><strong>Client :</strong> {$chantier->client->name}</p>
            <p><strong>Date du rapport :</strong> {now()->format('d/m/Y')}</p>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>Budgété</th>
                        <th>Réel</th>
                        <th>Écart</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th colspan="4" style="text-align:center; background-color: #e9e9e9;">Revenus</th>
                    </tr>
                    <tr>
                        <td>Total Ventes</td>
                        <td>{$formatNumber($chantier->budgeted_revenue)}</td>
                        <td>{$formatNumber($chantier->total_sales_revenue)}</td>
                        <td style="{$formatColor($chantier->total_sales_revenue - $chantier->budgeted_revenue)}">{$formatNumber($chantier->total_sales_revenue - $chantier->budgeted_revenue)}</td>
                    </tr>
                    <tr>
                        <th colspan="4" style="text-align:center; background-color: #e9e9e9;">Coûts</th>
                    </tr>
                    <tr>
                        <td>Main d'œuvre</td>
                        <td>{$formatNumber($chantier->budgeted_labor_cost)}</td>
                        <td>{$formatNumber($chantier->total_labor_cost)}</td>
                        <td style="{$formatColor($chantier->budgeted_labor_cost - $chantier->total_labor_cost)}">{$formatNumber($chantier->budgeted_labor_cost - $chantier->total_labor_cost)}</td>
                    </tr>
                    <tr>
                        <td>Achats</td>
                        <td>{$formatNumber(0)}</td>
                        <td>{$formatNumber($chantier->total_purchase_cost)}</td>
                        <td style="{$formatColor(0 - $chantier->total_purchase_cost)}">{$formatNumber(0 - $chantier->total_purchase_cost)}</td>
                    </tr>
                    <tr>
                        <td>Matériaux (OF)</td>
                        <td>{$formatNumber($chantier->budgeted_material_cost)}</td>
                        <td>{$formatNumber($chantier->total_material_cost)}</td>
                        <td style="{$formatColor($chantier->budgeted_material_cost - $chantier->total_material_cost)}">{$formatNumber($chantier->budgeted_material_cost - $chantier->total_material_cost)}</td>
                    </tr>
                    <tr>
                        <td>Location</td>
                        <td>{$formatNumber($chantier->budgeted_rental_cost)}</td>
                        <td>{$formatNumber($chantier->total_rental_cost)}</td>
                        <td style="{$formatColor($chantier->budgeted_rental_cost - $chantier->total_rental_cost)}">{$formatNumber($chantier->budgeted_rental_cost - $chantier->total_rental_cost)}</td>
                    </tr>
                    <tr class="total">
                        <td>TOTAL COÛTS</td>
                        <td>{$formatNumber($chantier->total_budgeted_cost)}</td>
                        <td>{$formatNumber($chantier->total_real_cost)}</td>
                        <td style="{$formatColor($chantier->total_budgeted_cost - $chantier->total_real_cost)}">{$formatNumber($chantier->total_budgeted_cost - $chantier->total_real_cost)}</td>
                    </tr>
                     <tr>
                        <th colspan="4" style="text-align:center; background-color: #e9e9e9;">Marge</th>
                    </tr>
                    <tr class="total">
                        <td>MARGE BRUTE</td>
                        <td>{$formatNumber($chantier->budgeted_margin)}</td>
                        <td>{$formatNumber($chantier->real_margin)}</td>
                        <td style="{$formatColor($chantier->margin_difference)}">{$formatNumber($chantier->margin_difference)}</td>
                    </tr>
                </tbody>
            </table>
        </body>
        </html>
        HTML;
    }
}
