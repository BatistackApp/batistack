<?php

namespace App\Enums\Paie;

enum PayrollExportFormat: string
{
    case GenericCSV = 'generic_csv';
    case Silae = 'sil_ae'; // Exemple de format Silae
    case Sage = 'sage';     // Exemple de format Sage
    // Ajoutez d'autres formats si nécessaire
}
