<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayrollExportFormat;
use App\Enums\Paie\PayrollVariableType; // Import the enum
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
                // En-têtes spécifiques à Silae (exemple basé sur des formats courants)
                $headers = [
                    'Matricule', 'Nom', 'Prénom', 'CodeRubrique', 'LibelleRubrique', 'Quantite', 'Montant', 'Unite', 'Periode'
                ];
                $delimiter = ';';
                break;

            case PayrollExportFormat::Sage:
                // En-têtes spécifiques à Sage (exemple basé sur des formats courants)
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
                    $quantity = 0;
                    $amount = 0;

                    // Distinguer quantité (heures) et montant (valeur monétaire)
                    if (in_array($variable->type, [
                        PayrollVariableType::StandardHour,
                        PayrollVariableType::Overtime25,
                        PayrollVariableType::Overtime50,
                        PayrollVariableType::NightHour,
                        PayrollVariableType::SundayHour,
                        PayrollVariableType::Absence, // Les absences peuvent être en quantité d'heures à déduire
                    ])) {
                        $quantity = $variable->quantity;
                    } else {
                        $amount = $variable->quantity; // Pour Bonus, ExpenseRefund, la quantité est le montant
                    }

                    $row = [
                        $employeeId,
                        $slip->employee->last_name,
                        $slip->employee->first_name,
                        $variable->code, // Utilisation du code de la variable comme CodeRubrique
                        $variable->label,
                        number_format($quantity, 2, '.', ''),
                        number_format($amount, 2, '.', ''),
                        $variable->unit,
                        $periodName,
                    ];
                    break;

                case PayrollExportFormat::Sage:
                    $row = [
                        $employeeId,
                        $employeeName,
                        $variable->code, // Utilisation du code de la variable comme RubriqueCode
                        $variable->label,
                        number_format($variable->quantity, 2, '.', ''), // Sage utilise souvent une seule valeur
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
