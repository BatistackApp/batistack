<?php

namespace App\Observers\RH;

use App\Enums\RH\TimesheetType;
use App\Models\Chantiers\Chantiers;
use App\Models\RH\Timesheet;
use DB;

class TimesheetObserver
{
    /**
     * @throws \Exception
     */
    public function saving(Timesheet $timesheet): void
    {
        // Règle : On ne peut pas pointer plus de 12h par jour (Code du travail / Alert)
        if ($timesheet->hours > 12) {
            // Tu pourrais throw une exception ou juste logguer
            // Pour l'instant on laisse passer mais on pourrait flaguer
            throw new \Exception('Impossible de saisir plus de 12h sur une journée.');
        }

        // Règle Métier 2 : Nettoyage logique
        // Si c'est un trajet ou une absence, on ne met pas de panier repas
        if (in_array($timesheet->type, [TimesheetType::Travel, TimesheetType::Absence])) {
            $timesheet->lunch_basket = false;
            $timesheet->travel_zone = false;
        }
    }

    public function saved(Timesheet $timesheet): void
    {
        // Recalcul du coût après sauvegarde
        $this->updateChantiersLaborCost($timesheet->chantiers_id);

        // Si on a changé de projet, il faut recalculer l'ancien aussi !
        if ($timesheet->wasChanged('project_id')) {
            $this->updateChantiersLaborCost($timesheet->getOriginal('chantiers_id'));
        }

        // --- NOUVEAU : Mise à jour du Kilométrage/Compteur d'heures de la Flotte ---
        if ($timesheet->fleet_id) {
            $fleet = $timesheet->fleet;

            // Si un kilométrage de fin est fourni et qu'il est supérieur à la valeur actuelle de l'actif
            if ($timesheet->end_mileage !== null && $timesheet->end_mileage > $fleet->mileage) {
                $fleet->updateQuietly([
                    'mileage' => $timesheet->end_mileage,
                ]);
            }

            // Si une lecture d'heures est fournie et qu'elle est supérieure à la valeur actuelle de l'actif
            if ($timesheet->hours_read !== null && $timesheet->hours_read > $fleet->hours_meter) {
                $fleet->updateQuietly([
                    'hours_meter' => $timesheet->hours_read,
                ]);
            }
        }
    }

    public function deleted(Timesheet $timesheet): void
    {
        $this->updateChantiersLaborCost($timesheet->chantiers_id);
    }

    /**
     * Méthode privée pour recalculer le coût global d'un chantier.
     * Appelée à chaque ajout/modif/suppression d'heure.
     */
    private function updateChantiersLaborCost(?int $chantiersId): void
    {
        if (!$chantiersId) return;

        // On fait une somme pondérée : Heures * Coût Horaire de l'employé à l'instant T
        // Note : Pour être puriste, on devrait historiser le coût horaire de l'employé,
        // mais pour une V1, utiliser le coût actuel de la fiche employé est standard.

        $totalCost = DB::table('timesheets')
            ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
            ->where('timesheets.chantiers_id', $chantiersId)
            ->where('timesheets.type', TimesheetType::Work->value) // On ne compte que le travail effectif (pas les trajets/absences)
            ->sum(DB::raw('timesheets.hours * employees.hourly_cost'));

        // Mise à jour silencieuse (pour ne pas déclencher l'observer Project)
        Chantiers::where('id', $chantiersId)->update(['total_labor_cost' => $totalCost]);
    }
}
