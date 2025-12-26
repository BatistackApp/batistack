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
                'MatriculeSalarie' => 'employee.id',
                'DateDebutPeriode' => 'slip.period.start_date|Ymd',
                'DateFinPeriode' => 'slip.period.end_date|Ymd',
                'CodeRubrique' => '@mapped_code',
                'LibelleRubrique' => 'variable.label',
                'Quantite' => '@quantity',
                'Montant' => '@amount',
                'Taux' => '@rate',
                'Base' => '@base',
            ],
            'code_mapping' => [
                // Heures
                'std_hour' => '100',
                'overtime_25' => '200',
                'overtime_50' => '205',
                'night_hour' => '300',
                'sunday_hour' => '310',
                'absence' => '900',
                // Primes & Indemnités
                'bonus_panier' => '510',
                'bonus_trajet' => '520',
                'bonus' => '500', // Prime générique
                // Remboursements
                'expense' => '600',
                'meal_voucher' => '700',
                'transport' => '750',
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
                'bonus_panier' => 'PAN',
                'bonus_trajet' => 'TRAJ',
                'bonus' => 'PRM',
                'expense' => 'NDF',
                'meal_voucher' => 'TR',
                'transport' => 'TRANS',
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
