<?php

namespace App\Enums\Fleets;

enum MaintenanceType: string
{
    case Scheduled = 'scheduled'; // Entretien programmé (vidange, révision)
    case Repair = 'repair';       // Réparation suite à une panne ou un dommage
    case TechnicalControl = 'technical_control'; // Contrôle technique
    case Other = 'other';         // Autre type de maintenance
}
