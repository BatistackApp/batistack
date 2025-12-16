<?php

namespace App\Observers\RH;

use App\Enums\RH\TimesheetType;
use App\Models\Chantiers\Chantiers;
use App\Models\GPAO\ProductionOrder;
use App\Models\Interventions\Intervention;
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

        // Règle Métier 3 : Un pointage ne peut être lié qu'à un chantier OU un OF OU une intervention, pas plusieurs
        $linkedEntities = collect([
            $timesheet->chantiers_id,
            $timesheet->production_order_id,
            $timesheet->intervention_id,
        ])->filter()->count();

        if ($linkedEntities > 1) {
            throw new \Exception('Un pointage ne peut être lié qu\'à un seul chantier, ordre de fabrication ou intervention.');
        }
    }

    public function saved(Timesheet $timesheet): void
    {
        // Recalcul du coût après sauvegarde
        $this->updateChantiersLaborCost($timesheet->chantiers_id);
        $this->updateProductionOrderLaborCost($timesheet->production_order_id);
        $this->updateInterventionLaborCost($timesheet->intervention_id);

        // Si on a changé de projet, il faut recalculer l'ancien aussi !
        if ($timesheet->wasChanged('chantiers_id')) {
            $this->updateChantiersLaborCost($timesheet->getOriginal('chantiers_id'));
        }
        if ($timesheet->wasChanged('production_order_id')) {
            $this->updateProductionOrderLaborCost($timesheet->getOriginal('production_order_id'));
        }
        if ($timesheet->wasChanged('intervention_id')) {
            $this->updateInterventionLaborCost($timesheet->getOriginal('intervention_id'));
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
                    'hours_meter' => $fleet->hours_meter, // Correction: Utiliser la valeur du timesheet
                ]);
            }
        }
    }

    public function deleted(Timesheet $timesheet): void
    {
        $this->updateChantiersLaborCost($timesheet->chantiers_id);
        $this->updateProductionOrderLaborCost($timesheet->production_order_id);
        $this->updateInterventionLaborCost($timesheet->intervention_id);
    }

    /**
     * Méthode privée pour recalculer le coût global d'un chantier.
     */
    private function updateChantiersLaborCost(?int $chantiersId): void
    {
        if (!$chantiersId) return;

        $chantier = Chantiers::find($chantiersId);
        if ($chantier) {
            $totalCost = $chantier->timesheets->sum(function (Timesheet $timesheet) {
                return $timesheet->cost;
            });
            $chantier->update(['total_labor_cost' => $totalCost]);
        }
    }

    /**
     * Méthode privée pour recalculer le coût global d'un ordre de fabrication.
     */
    private function updateProductionOrderLaborCost(?int $productionOrderId): void
    {
        if (!$productionOrderId) return;

        $productionOrder = ProductionOrder::find($productionOrderId);
        if ($productionOrder) {
            $productionOrder->recalculateLaborCost(); // Appel de la méthode du modèle
        }
    }

    /**
     * Méthode privée pour recalculer le coût global d'une intervention.
     */
    private function updateInterventionLaborCost(?int $interventionId): void
    {
        if (!$interventionId) return;

        $intervention = Intervention::find($interventionId);
        if ($intervention) {
            $intervention->recalculateLaborCost(); // Appel de la méthode du modèle
        }
    }
}
