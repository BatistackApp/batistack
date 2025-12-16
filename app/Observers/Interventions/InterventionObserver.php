<?php

namespace App\Observers\Interventions;

use App\Models\Interventions\Intervention;
use App\Notifications\Interventions\InterventionNotification;

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
