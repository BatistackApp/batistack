<?php

namespace App\Observers\Fleets;

use App\Enums\Fleets\FleetAssignmentStatus;
use App\Models\Fleets\FleetAssignment;
use App\Models\RH\Team;
use App\Notifications\Fleets\FleetAssignedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FleetAssignmentObserver
{
    /**
     * Handle the FleetAssignment "creating" event.
     *
     * @throws ValidationException
     */
    public function creating(FleetAssignment $fleetAssignment): void
    {
        $this->checkForConflicts($fleetAssignment);
    }

    /**
     * Handle the FleetAssignment "updating" event.
     *
     * @throws ValidationException
     */
    public function updating(FleetAssignment $fleetAssignment): void
    {
        // On ne vérifie les conflits que si les dates ou le véhicule ont changé
        if ($fleetAssignment->isDirty(['start_date', 'end_date', 'fleet_id'])) {
            $this->checkForConflicts($fleetAssignment);
        }
    }

    /**
     * Vérifie les conflits de dates pour une assignation de véhicule.
     */
    protected function checkForConflicts(FleetAssignment $assignment): void
    {
        $query = FleetAssignment::query()
            ->where('fleet_id', $assignment->fleet_id)
            ->where('id', '!=', $assignment->id)
            ->where('status', '!=', FleetAssignmentStatus::Cancelled);

        $newStart = $assignment->start_date;
        $newEnd = $assignment->end_date;

        $query->where(function ($q) use ($newStart, $newEnd) {
            $q->where(function ($subQ) use ($newStart, $newEnd) {
                $subQ->whereNotNull('end_date')
                     ->where('end_date', '>=', $newStart);
                if ($newEnd) {
                    $subQ->where('start_date', '<=', $newEnd);
                }
            })
            ->orWhere(function ($subQ) use ($newEnd) {
                $subQ->whereNull('end_date');
                if ($newEnd) {
                    $subQ->where('start_date', '<=', $newEnd);
                }
            });
        });

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'fleet_id' => "Ce véhicule est déjà assigné à une autre entité sur cette période.",
            ]);
        }
    }

    /**
     * Handle the FleetAssignment "created" event.
     */
    public function created(FleetAssignment $fleetAssignment): void
    {
        $this->sendNotification($fleetAssignment, 'created');
    }

    /**
     * Handle the FleetAssignment "updated" event.
     */
    public function updated(FleetAssignment $fleetAssignment): void
    {
        if ($fleetAssignment->isDirty(['start_date', 'end_date', 'fleet_id', 'assignable_id', 'assignable_type'])) {
            $this->sendNotification($fleetAssignment, 'updated');
        }
    }

    /**
     * Handle the FleetAssignment "saved" event.
     */
    public function saved(FleetAssignment $fleetAssignment): void
    {
        $today = Carbon::today();
        $newStatus = null;

        if ($fleetAssignment->start_date->isFuture()) {
            $newStatus = FleetAssignmentStatus::Scheduled;
        } elseif ($fleetAssignment->start_date->isPast() || $fleetAssignment->start_date->isToday()) {
            if ($fleetAssignment->end_date === null || $fleetAssignment->end_date->isFuture() || $fleetAssignment->end_date->isToday()) {
                $newStatus = FleetAssignmentStatus::Active;
            } elseif ($fleetAssignment->end_date->isPast()) {
                $newStatus = FleetAssignmentStatus::Completed;
            }
        }

        if ($newStatus && $fleetAssignment->status !== $newStatus) {
            $fleetAssignment->updateQuietly(['status' => $newStatus]);
        }
    }

    /**
     * Handle the FleetAssignment "deleted" event.
     */
    public function deleted(FleetAssignment $fleetAssignment): void
    {
        $this->sendNotification($fleetAssignment, 'deleted');
    }

    /**
     * Centralise l'envoi de notifications.
     */
    private function sendNotification(FleetAssignment $fleetAssignment, string $type): void
    {
        $assignable = $fleetAssignment->assignable;

        if (!$assignable) {
            return;
        }

        // Si c'est une équipe, on notifie le chef d'équipe
        if ($assignable instanceof Team && $assignable->leader) {
            $assignable->leader->notify(new FleetAssignedNotification($fleetAssignment, $type));
        }
        // Si c'est un employé (ou toute autre entité notifiable)
        elseif (method_exists($assignable, 'notify')) {
            $assignable->notify(new FleetAssignedNotification($fleetAssignment, $type));
        }
    }
}
