<?php

namespace App\Observers\Fleets;

use App\Models\Fleets\Maintenance;

class MaintenanceObserver
{
    public function saved(Maintenance $maintenance): void
    {
        $fleet = $maintenance->fleet;

        if ($fleet &&
            ($fleet->last_check_date === null || $maintenance->maintenance_date > $fleet->last_check_date)
        ) {
            // Mise à jour du champ sans déclencher une nouvelle cascade d'événements
            // (utiliser update directement ou la méthode saveQuietly si vous aviez un Trait pour la gestion des événements).
            $fleet->updateQuietly([
                'last_check_date' => $maintenance->maintenance_date,
            ]);

            //  mettre à jour le kilométrage/compteur d'heures
            $fleet->updateQuietly([
                'last_check_date' => $maintenance->maintenance_date,
                'mileage' => max($fleet->mileage, $maintenance->current_mileage),
                // Assumant que la table maintenance a un champ 'current_mileage'
            ]);
        }
    }
}
