<?php

namespace App\Services\Paie;

use App\Models\Paie\PayrollSlip;
use Illuminate\Support\Str;

class PayrollExportService
{
    /**
     * Génère un fichier CSV à partir des variables d'un bulletin de paie.
     *
     * @param PayrollSlip $slip
     * @return string Le contenu du fichier CSV.
     */
    public function generateCsv(PayrollSlip $slip): string
    {
        $csvData = [];
        // En-tête du CSV
        $csvData[] = ['code', 'label', 'quantity', 'unit'];

        // Récupération des variables de paie
        $variables = $slip->variables;

        foreach ($variables as $variable) {
            $csvData[] = [
                $variable->code,
                $variable->label,
                $variable->quantity,
                $variable->unit,
            ];
        }

        // Conversion du tableau en chaîne de caractères CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    /**
     * Génère un nom de fichier unique pour l'export.
     *
     * @param PayrollSlip $slip
     * @return string
     */
    public function generateFileName(PayrollSlip $slip): string
    {
        $employeeName = Str::slug($slip->employee->full_name);
        $period = $slip->period->name; // ex: "Janvier 2025"

        return "export-paie_{$period}_{$employeeName}_{$slip->id}.csv";
    }
}
