<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payroll Export Formats Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines the structure for different payroll export formats.
    | Each format has a specific set of headers and a mapping to retrieve
    | the corresponding data from the application's models.
    |
    | - 'delimiter': The CSV delimiter (e.g., ';', ',').
    | - 'headers': An ordered array of column headers for the CSV file.
    | - 'mapping': An associative array where the key is the header and the
    |   value is the source of the data.
    | - 'code_mapping': (Optional) A mapping between internal PayrollVariableType
    |   values and the external software codes.
    |
    | Data Sources Syntax:
    | - 'model.property.sub_property': Dot notation to access related model data.
    |   'slip' refers to the PayrollSlip model.
    |   'variable' refers to the PayrollVariable model.
    |   'employee' refers to the Employee model associated with the slip.
    |   Example: 'employee.ssn' would get the social security number.
    |
    | - '@directive': A special directive for calculated or conditional values.
    |   These are resolved by a dedicated method in the PayrollExportService.
    |   Example: '@quantity', '@amount', '@mapped_code'.
    |
    */

    'formats' => [

        'silae' => [
            'delimiter' => ';',
            'headers' => [
                'MatriculeSalarie',
                'DateDebutPeriode',
                'DateFinPeriode',
                'CodeRubrique',
                'LibelleRubrique',
                'Quantite',
                'Montant',
                'Taux',
                'Base',
            ],
            'mapping' => [
                'MatriculeSalarie' => 'employee.id', // TODO: Remplacer par le vrai matricule si différent de l'ID
                'DateDebutPeriode' => 'slip.period.start_date|Ymd',
                'DateFinPeriode' => 'slip.period.end_date|Ymd',
                'CodeRubrique' => '@mapped_code', // Utilise le mapping défini ci-dessous
                'LibelleRubrique' => 'variable.label',
                'Quantite' => '@quantity',
                'Montant' => '@amount',
                'Taux' => '@rate',
                'Base' => '@base',
            ],
            'code_mapping' => [
                // Mapping PayrollVariableType (interne) => Code Silae (externe)
                'std_hour' => '100',       // Heures normales
                'overtime_25' => '200',    // Heures sup 25%
                'overtime_50' => '205',    // Heures sup 50%
                'night_hour' => '300',     // Heures de nuit
                'sunday_hour' => '310',    // Heures dimanche
                'absence' => '900',        // Absences
                'bonus' => '500',          // Primes génériques (à affiner selon le code variable)
                'expense' => '600',        // Remboursement de frais
            ],
        ],

        'sage' => [
            'delimiter' => ';',
            'headers' => [
                'EmployeNum', 'NomEmploye', 'RubriqueCode', 'LibelleRubrique', 'Valeur', 'Unite', 'DateDebut', 'DateFin'
            ],
            'mapping' => [
                'EmployeNum' => 'employee.id',
                'NomEmploye' => 'employee.full_name',
                'RubriqueCode' => '@mapped_code',
                'LibelleRubrique' => 'variable.label',
                'Valeur' => 'variable.quantity',
                'Unite' => 'variable.unit',
                'DateDebut' => 'slip.period.start_date|Ymd',
                'DateFin' => 'slip.period.end_date|Ymd',
            ],
            'code_mapping' => [
                'std_hour' => 'HN',
                'overtime_25' => 'HS25',
                'overtime_50' => 'HS50',
                'night_hour' => 'HNUIT',
                'sunday_hour' => 'HDIM',
                'absence' => 'ABS',
                'bonus' => 'PRIME',
                'expense' => 'NDF',
            ],
        ],

        'generic_csv' => [
            'delimiter' => ';',
            'headers' => [
                'EmployeeID', 'EmployeeName', 'PeriodName', 'VariableCode', 'VariableLabel', 'Value', 'Unit', 'VariableType'
            ],
            'mapping' => [
                'EmployeeID' => 'employee.id',
                'EmployeeName' => 'employee.full_name',
                'PeriodName' => 'slip.period.name',
                'VariableCode' => 'variable.code',
                'VariableLabel' => 'variable.label',
                'Value' => 'variable.quantity',
                'Unit' => 'variable.unit',
                'VariableType' => 'variable.type.value',
            ],
        ],

    ],
];
