<?php

namespace App\Observers\RH;

use App\Enums\RH\TimesheetType;
use App\Models\Chantiers\Chantiers;
use App\Models\GPAO\ProductionOrder;
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

        // Règle Métier 3 : Un pointage ne peut être lié qu'à un chantier OU un OF, pas les deux
        if ($timesheet->chantiers_id && $timesheet->production_order_id) {
            throw new \Exception('Un pointage ne peut pas être lié à la fois à un chantier et à un ordre de fabrication.');
        }
    }

    public function saved(Timesheet $timesheet): void
    {
        // Recalcul du coût après sauvegarde
        $this->updateChantiersLaborCost($timesheet->chantiers_id);
        $this->updateProductionOrderLaborCost($timesheet->production_order_id);

        // Si on a changé de projet, il faut recalculer l'ancien aussi !
        if ($timesheet->wasChanged('chantiers_id')) {
            $this->updateChantiersLaborCost($timesheet->getOriginal('chantiers_id'));
        }
        if ($timesheet->wasChanged('production_order_id')) {
            $this->updateProductionOrderLaborCost($timesheet->getOriginal('production_order_id'));
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
        $this->updateProductionOrderLaborCost($timesheet->production_order_id);
    }

    /**
     * Méthode privée pour recalculer le coût global d'un chantier.
     */
    private function updateChantiersLaborCost(?int $chantiersId): void
    {
        if (!$chantiersId) return;

        $totalCost = $this->calculateLaborCost('chantiers_id', $chantiersId);
        Chantiers::where('id', $chantiersId)->update(['total_labor_cost' => $totalCost]);
    }

    /**
     * Méthode privée pour recalculer le coût global d'un ordre de fabrication.
     */
    private function updateProductionOrderLaborCost(?int $productionOrderId): void
    {
        if (!$productionOrderId) return;

        $totalCost = $this->calculateLaborCost('production_order_id', $productionOrderId);
        ProductionOrder::where('id', $productionOrderId)->update(['total_labor_cost' => $totalCost]);
    }

    /**
     * Calcule le coût de la main-d'œuvre pour une entité donnée (chantier ou OF).
     */
    private function calculateLaborCost(string $foreignKey, int $id): float
    {
        $costMultipliers = [
            TimesheetType::Work->value => 1.0,
            TimesheetType::Travel->value => 1.0,
            TimesheetType::Overtime25->value => 1.25,
            TimesheetType::Overtime50->value => 1.50,
            TimesheetType::NightHour->value => 1.25,
            TimesheetType::SundayHour->value => 1.50,
        ];

        $totalCost = 0;

        foreach ($costMultipliers as $typeValue => $multiplier) {
            $costForType = DB::table('timesheets')
                ->join('employees', 'timesheets.employee_id', '=', 'employees.id')
                ->where("timesheets.{$foreignKey}", $id)
                ->where('timesheets.type', $typeValue)
                ->sum(DB::raw("timesheets.hours * employees.hourly_cost * {$multiplier}"));
            $totalCost += $costForType;
        }

        return $totalCost;
    }
}
