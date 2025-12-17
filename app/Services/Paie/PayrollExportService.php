<?php

namespace App\Services\Paie;

use App\Enums\Paie\PayrollExportFormat;
use App\Enums\Paie\PayrollVariableType;
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
                // Spécifications Silae simulées (basées sur des formats courants)
                // L'ordre et les noms des colonnes sont CRUCIAUX pour Silae.
                // Ces noms sont des placeholders et doivent être remplacés par les noms exacts de Silae
                // fournis dans la documentation d'intégration.
                $headers = [
                    'MatriculeSalarie',     // ID unique de l'employé
                    'NomSalarie',           // Nom de famille de l'employé
                    'PrenomSalarie',        // Prénom de l'employé
                    'DateDebutPeriode',     // Date de début de la période de paie (YYYYMMDD)
                    'DateFinPeriode',       // Date de fin de la période de paie (YYYYMMDD)
                    'CodeRubrique',         // Code de la rubrique de paie (ex: HN, HS25, RFRAIS)
                    'LibelleRubrique',      // Libellé de la rubrique de paie
                    'Quantite',             // Quantité (pour les heures, jours, etc.)
                    'Montant',              // Montant (pour les primes, remboursements, etc.)
                    'Taux',                 // Taux horaire ou autre taux (si applicable à la rubrique)
                    'Base',                 // Base de calcul (si applicable à la rubrique)
                    'Unite',                // Unité de la quantité (h, €, j)
                    // TODO: Ajouter d'autres colonnes spécifiques à Silae si nécessaire (ex: CentreCout, Service, TypeContrat, DateEntree, DateSortie...)
                ];
                $delimiter = ';';
                break;

            case PayrollExportFormat::Sage:
                // Spécifications Sage simulées
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
        $employeeLastName = $slip->employee->last_name;
        $employeeFirstName = $slip->employee->first_name;
        $employeeName = $slip->employee->full_name;
        $periodName = $slip->period->name;
        $periodStartDate = $slip->period->start_date->format('Ymd'); // Format Silae YYYYMMDD
        $periodEndDate = $slip->period->end_date->format('Ymd');     // Format Silae YYYYMMDD

        // Récupération des variables de paie
        $variables = $slip->variables;

        foreach ($variables as $variable) {
            $row = [];
            switch ($format) {
                case PayrollExportFormat::Silae:
                    $quantity = 0;
                    $amount = 0;
                    $rate = ''; // Taux
                    $base = ''; // Base de calcul

                    // Distinguer quantité (heures) et montant (valeur monétaire)
                    if (in_array($variable->type, [
                        PayrollVariableType::StandardHour,
                        PayrollVariableType::Overtime25,
                        PayrollVariableType::Overtime50,
                        PayrollVariableType::NightHour,
                        PayrollVariableType::SundayHour,
                        PayrollVariableType::Absence,
                    ])) {
                        $quantity = $variable->quantity;
                        // TODO: Calculer le taux ou la base si Silae l'attend pour les heures (ex: taux horaire de l'employé)
                        // $rate = number_format($slip->employee->hourly_rate, 2, ',', '');
                    } else {
                        $amount = $variable->quantity; // Pour Bonus, ExpenseRefund, la quantité est le montant
                        // TODO: Calculer le taux ou la base si Silae l'attend pour les montants
                    }

                    $row = [
                        $employeeId,                                    // MatriculeSalarie
                        $employeeLastName,                              // NomSalarie
                        $employeeFirstName,                             // PrenomSalarie
                        $periodStartDate,                               // DateDebutPeriode
                        $periodEndDate,                                 // DateFinPeriode
                        $variable->code,                                // CodeRubrique
                        $variable->label,                               // LibelleRubrique
                        number_format($quantity, 2, ',', ''),           // Quantite
                        number_format($amount, 2, ',', ''),             // Montant
                        $rate,                                          // Taux
                        $base,                                          // Base
                        $variable->unit,                                // Unite
                    ];
                    break;

                case PayrollExportFormat::Sage:
                    $row = [
                        $employeeId,
                        $employeeName,
                        $variable->code,
                        $variable->label,
                        number_format($variable->quantity, 2, ',', ''),
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
                        number_format($variable->quantity, 2, ',', ''),
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
