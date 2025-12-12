<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayrollExportFormat;
use App\Models\Paie\PayrollSlip;
use Illuminate\Support\Str;

class PayrollExportService
{
    /**
     * Génère un fichier CSV à partir des variables d'un bulletin de paie,
     * adapté à un format d'export spécifique.
     *
     * @param PayrollSlip $slip
     * @param PayrollExportFormat $format Le format d'export désiré (ex: Silae, Sage, GenericCSV).
     * @return string Le contenu du fichier CSV.
     */
    public function generateCsv(PayrollSlip $slip, PayrollExportFormat $format = PayrollExportFormat::GenericCSV): string
    {
        $csvData = [];
        $delimiter = ';'; // Délimiteur par défaut pour la France
        $headers = [];

        // Définition des en-têtes et du délimiteur selon le format
        switch ($format) {
            case PayrollExportFormat::Silae:
                // TODO: Définir les en-têtes et l'ordre des colonnes spécifiques à Silae
                // Ceci est un exemple, les spécifications réelles de Silae sont complexes.
                $headers = [
                    'Matricule', 'Nom', 'Prénom', 'CodeRubrique', 'LibelleRubrique', 'Quantite', 'Montant', 'Unite', 'Periode'
                ];
                $delimiter = ';';
                break;

            case PayrollExportFormat::Sage:
                // TODO: Définir les en-têtes et l'ordre des colonnes spécifiques à Sage
                // Ceci est un exemple.
                $headers = [
                    'EmployeNum', 'NomEmploye', 'RubriqueCode', 'LibelleRubrique', 'Valeur', 'Unite', 'DateDebut', 'DateFin'
                ];
                $delimiter = ';';
                break;

            case PayrollExportFormat::GenericCSV:
            default:
                $headers = [
                    'EmployeeID', 'EmployeeName', 'PeriodName', 'VariableCode', 'VariableLabel', 'Value', 'Unit', 'VariableType'
                ];
                $delimiter = ';';
                break;
        }

        $csvData[] = $headers;

        // Informations générales du bulletin
        $employeeId = $slip->employee->id;
        $employeeName = $slip->employee->full_name;
        $periodName = $slip->period->name;

        // Récupération des variables de paie
        $variables = $slip->variables;

        foreach ($variables as $variable) {
            $row = [];
            switch ($format) {
                case PayrollExportFormat::Silae:
                    // TODO: Mapper les données du variable aux colonnes Silae
                    $row = [
                        $employeeId,
                        $slip->employee->last_name,
                        $slip->employee->first_name,
                        $variable->code,
                        $variable->label,
                        number_format($variable->quantity, 2, '.', ''),
                        // Silae peut nécessiter une distinction entre quantité et montant
                        '', // Montant (si différent de quantité)
                        $variable->unit,
                        $periodName,
                    ];
                    break;

                case PayrollExportFormat::Sage:
                    // TODO: Mapper les données du variable aux colonnes Sage
                    $row = [
                        $employeeId,
                        $employeeName,
                        $variable->code,
                        $variable->label,
                        number_format($variable->quantity, 2, '.', ''),
                        $variable->unit,
                        $slip->period->start_date->format('Ymd'),
                        $slip->period->end_date->format('Ymd'),
                    ];
                    break;

                case PayrollExportFormat::GenericCSV:
                default:
                    $row = [
                        $employeeId,
                        $employeeName,
                        $periodName,
                        $variable->code,
                        $variable->label,
                        number_format($variable->quantity, 2, '.', ''),
                        $variable->unit,
                        $variable->type->value,
                    ];
                    break;
            }
            $csvData[] = $row;
        }

        // Conversion du tableau en chaîne de caractères CSV
        $output = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($output, $row, $delimiter, '"');
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
     * @param PayrollExportFormat $format
     * @return string
     */
    public function generateFileName(PayrollSlip $slip, PayrollExportFormat $format = PayrollExportFormat::GenericCSV): string
    {
        $employeeName = Str::slug($slip->employee->full_name);
        $period = Str::slug($slip->period->name);
        $formatSlug = Str::slug($format->value);

        return "export-paie_{$period}_{$employeeName}_{$formatSlug}_{$slip->id}.csv";
    }
}
