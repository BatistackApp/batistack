<?php

namespace App\Services\Paie;

use App\Models\Paie\PayrollPeriods;
use App\Models\Paie\PayrollSlip;
use App\Models\Paie\PayrollVariable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PayrollExportService
{
    protected array $exportHeaders = [
        'COMPANY_CODE', 'EMPLOYEE_ID_TIERS', 'PAYROLL_PERIOD',
        'HN_QTY', 'HN_UNIT', 'HS25_QTY', 'HS50_QTY', 'RFRAIS_AMT',
    ];

    /**
     * Génère l'export de paie pour une période donnée.
     * @param PayrollPeriods $period La période à exporter.
     * @return string Le chemin vers le fichier généré.
     * @throws \Exception
     */
    public function generateExportFile(PayrollPeriods $period, string $format = 'csv'): string
    {
        // 1. On récupère UNIQUEMENT les bulletins validés pour la période
        $slips = PayrollSlip::where('payroll_period_id', $period->id)
            ->where('is_validated', true)
            ->with(['employee', 'variables']) // Charger les relations nécessaires
            ->get();

        if ($slips->isEmpty()) {
            throw new \Exception("Aucun bulletin validé pour la période {$period->name}.");
        }

        // 2. Préparation des données d'export
        $data = $this->formatSlipsForExport($slips);

        // 3. Génération du fichier (CSV par défaut)
        $fileName = "export_paie_{$period->id}_" . now()->format('YmdHis') . ".{$format}";

        // Utilisation du disque 'exports' pour stocker les fichiers temporairement
        $filePath = 'exports/payroll/' . $fileName;

        $csvContent = $this->buildCsvContent($data);

        // Stocker le fichier sur le disque configuré (ex: local ou S3)
        Storage::put($filePath, $csvContent);

        return $filePath;
    }

    /**
     * Formate la collection de bulletins en un tableau de lignes d'export plat.
     * @param Collection<PayrollSlip> $slips
     * @return array
     */
    protected function formatSlipsForExport(Collection $slips): array
    {
        $exportData = [];

        foreach ($slips as $slip) {
            $row = [
                'COMPANY_CODE' => $slip->employee->company->payroll_code ?? $slip->employee->company_id, // Code entreprise
                'EMPLOYEE_ID_TIERS' => $slip->employee->payroll_id, // ID unique de l'employé chez le tiers
                'PAYROLL_PERIOD' => $slip->period->start_date->format('Ym'),

                // Initialisation des variables spécifiques (quantité/montant par défaut à 0)
                'HN_QTY' => 0.0,
                'HS25_QTY' => 0.0,
                'HS50_QTY' => 0.0,
                'RFRAIS_AMT' => 0.0,
            ];

            /** @var PayrollVariable $variable */
            foreach ($slip->variables as $variable) {
                // Mapping des codes Batistack (ex: 'HN') vers la structure d'export
                $code = $variable->code;

                // Logique pour les Heures (quantité)
                if (str_ends_with($code, 'QTY') && array_key_exists($code, $row)) {
                    $row[$code] = $variable->quantity;
                }
                // Logique pour les Montants (remboursements, primes)
                elseif (str_ends_with($code, 'AMT') && array_key_exists($code, $row)) {
                    // Si c'est un montant, la 'quantity' est le montant dans notre modèle
                    $row[$code] = $variable->quantity;
                }
                // Cas spécifique du Remboursement de Frais
                elseif ($code === 'RFRAIS') {
                    $row['RFRAIS_AMT'] = $variable->quantity;
                }
                // NOTE: La logique de mapping ici doit être TRES spécifique au logiciel cible.
            }

            // On s'assure que toutes les colonnes requises dans $exportHeaders sont présentes, même si vides
            $finalRow = array_merge(array_fill_keys($this->exportHeaders, ''), $row);

            // On s'assure de l'ordre des colonnes pour le CSV
            $exportData[] = array_intersect_key($finalRow, array_flip($this->exportHeaders));
        }

        return $exportData;
    }

    /**
     * Construit le contenu CSV.
     * @param array $data Les données d'export formatées.
     * @return string Le contenu CSV.
     */
    protected function buildCsvContent(array $data): string
    {
        $csvLines = [];

        // En-tête (nécessaire pour le format tiers)
        $csvLines[] = implode(';', $this->exportHeaders);

        foreach ($data as $row) {
            // Implode les valeurs avec le séparateur (souvent ; ou ,)
            $csvLines[] = implode(';', array_values($row));
        }

        // Utilisation de "\r\n" pour la compatibilité Windows/Excel souvent exigée par les logiciels de paie
        return implode("\r\n", $csvLines);
    }

    /**
     * Permet de télécharger le fichier généré.
     * @param string $filePath Le chemin du fichier (relatif au Storage).
     * @return BinaryFileResponse
     */
    public function downloadExportFile(string $filePath): BinaryFileResponse
    {
        // Assurez-vous que le fichier existe avant de le renvoyer
        if (!Storage::exists($filePath)) {
            abort(404, "Le fichier d'export Paie n'existe pas.");
        }

        return response()->download(Storage::path($filePath))->deleteFileAfterSend(true);
    }

}
