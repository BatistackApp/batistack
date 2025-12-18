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
    }

    /**
     * Handle the Intervention "updated" event.
     */
    public function updated(Intervention $intervention): void
    {
        if ($intervention->isDirty('status')) {
            $this->sendNotification($intervention, 'status_changed');

            // Si le statut passe à "Terminé", comptabiliser les coûts
            if ($intervention->status === InterventionStatus::Completed) {
                try {
                    $comptaService = new InterventionComptaService();
                    $comptaService->postInterventionCosts($intervention);
                    Log::info("Coûts pour l'intervention {$intervention->id} comptabilisés avec succès.");
                } catch (\Exception $e) {
                    Log::error("Erreur lors de la comptabilisation des coûts pour l'intervention {$intervention->id}: " . $e->getMessage());
                }
            }
        }
        // TODO: Ajouter d'autres conditions pour les notifications de mise à jour si nécessaire
    }

    /**
     * Handle the Intervention "deleted" event.
     */
    public function deleted(Intervention $intervention): void
    {
        $this->sendNotification($intervention, 'deleted');
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
