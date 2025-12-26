<?php

namespace App\Observers\Interventions;

use App\Enums\Interventions\InterventionStatus;
use App\Models\Interventions\Intervention;
use App\Notifications\Interventions\InterventionNotification;
use App\Services\Comptabilite\InterventionComptaService;
use Illuminate\Support\Facades\Log;

class InterventionObserver
{
    /**
     * Handle the Intervention "created" event.
     */
    public function created(Intervention $intervention): void
    {
        $this->sendNotification($intervention, 'created');

        if ($intervention->chantier_id) {
            $intervention->chantier->increment('total_labor_cost', $intervention->total_labor_cost);
            $intervention->chantier->increment('total_material_cost', $intervention->total_material_cost);
        }
    }

    /**
     * Handle the Intervention "updated" event.
     */
    public function updated(Intervention $intervention): void
    {
        // Si les coûts ont changé
        if ($intervention->isDirty('total_labor_cost', 'total_material_cost')) {
            $oldLaborCost = $intervention->getOriginal('total_labor_cost');
            $newLaborCost = $intervention->total_labor_cost;
            $oldMaterialCost = $intervention->getOriginal('total_material_cost');
            $newMaterialCost = $intervention->total_material_cost;

            if ($intervention->chantier_id) {
                $intervention->chantier->increment('total_labor_cost', $newLaborCost - $oldLaborCost);
                $intervention->chantier->increment('total_material_cost', $newMaterialCost - $oldMaterialCost);
            }
        }

        // Si le chantier a changé
        if ($intervention->isDirty('chantier_id')) {
            // Retirer l'ancien coût de l'ancien chantier
            if ($intervention->getOriginal('chantier_id')) {
                $oldChantier = \App\Models\Chantiers\Chantiers::find($intervention->getOriginal('chantier_id'));
                if ($oldChantier) {
                    $oldChantier->decrement('total_labor_cost', $intervention->getOriginal('total_labor_cost'));
                    $oldChantier->decrement('total_material_cost', $intervention->getOriginal('total_material_cost'));
                }
            }
            // Ajouter le nouveau coût au nouveau chantier
            if ($intervention->chantier_id) {
                $intervention->chantier->increment('total_labor_cost', $intervention->total_labor_cost);
                $intervention->chantier->increment('total_material_cost', $intervention->total_material_cost);
            }
        }

        if ($intervention->isDirty('status')) {
            $this->sendNotification($intervention, 'status_changed');

            // Si le statut passe à "Terminé"
            if ($intervention->status === InterventionStatus::Completed) {

                // 1. Générer la facture (REVENU) si l'intervention est facturable
                // On le fait AVANT la comptabilisation des coûts pour avoir potentiellement un lien
                if ($intervention->is_billable && !$intervention->sales_document_id) {
                    try {
                        $intervention->generateSalesDocument();
                        Log::info("Facture générée pour l'intervention {$intervention->id}.");
                    } catch (\Exception $e) {
                        Log::error("Erreur lors de la génération de la facture pour l'intervention {$intervention->id}: " . $e->getMessage());
                    }
                }

                // 2. Comptabiliser les coûts (CHARGES ANALYTIQUES)
                // Uniquement si pas déjà fait
                if (!$intervention->costs_posted_to_compta) {
                    try {
                        $comptaService = new InterventionComptaService();
                        $comptaService->postInterventionCosts($intervention);
                        Log::info("Coûts pour l'intervention {$intervention->id} comptabilisés avec succès.");
                    } catch (\Exception $e) {
                        Log::error("Erreur lors de la comptabilisation des coûts pour l'intervention {$intervention->id}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    /**
     * Handle the Intervention "deleted" event.
     */
    public function deleted(Intervention $intervention): void
    {
        $this->sendNotification($intervention, 'deleted');

        if ($intervention->chantier_id) {
            $intervention->chantier->decrement('total_labor_cost', $intervention->total_labor_cost);
            $intervention->chantier->decrement('total_material_cost', $intervention->total_material_cost);
        }
    }

    /**
     * Send a notification to the assigned technician.
     */
    private function sendNotification(Intervention $intervention, string $type): void
    {
        $technician = $intervention->technician;

        if ($technician && method_exists($technician, 'notify')) {
            $technician->notify(new InterventionNotification($intervention, $type));
        }
        // TODO: Notifier le client si nécessaire
    }
}
