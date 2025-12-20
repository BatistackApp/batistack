<?php

namespace App\Observers\Fleets;

use App\Enums\Fleets\FleetAssignmentStatus;
use App\Models\Fleets\FleetAssignment;
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
     *
     * @param FleetAssignment $assignment
     * @throws ValidationException
     */
    protected function checkForConflicts(FleetAssignment $assignment): void
    {
        $query = FleetAssignment::query()
            ->where('fleet_id', $assignment->fleet_id)
            ->where('id', '!=', $assignment->id) // Exclure l'assignation actuelle en cas de mise à jour
            ->where('status', '!=', FleetAssignmentStatus::Cancelled); // Ignorer les assignations annulées

        // Logique de chevauchement :
        // (StartA <= EndB) et (EndA >= StartB)
        // Si End est null, on considère que c'est une date très lointaine (ou infini)

        $newStart = $assignment->start_date;
        $newEnd = $assignment->end_date;

        $query->where(function ($q) use ($newStart, $newEnd) {
            // Cas 1 : L'assignation existante a une date de fin définie
            $q->where(function ($subQ) use ($newStart, $newEnd) {
                $subQ->whereNotNull('end_date')
                     ->where('end_date', '>=', $newStart);

                if ($newEnd) {
                    $subQ->where('start_date', '<=', $newEnd);
                }
            })
            // Cas 2 : L'assignation existante n'a pas de date de fin (elle court toujours)
            ->orWhere(function ($subQ) use ($newEnd) {
                $subQ->whereNull('end_date');

                if ($newEnd) {
                    $subQ->where('start_date', '<=', $newEnd);
                }
                // Si newEnd est null aussi, alors ça chevauche forcément car les deux sont infinis
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
        $assignable = $fleetAssignment->assignable;

        // Envoyer une notification à l'entité assignée (employé ou équipe)
        if ($assignable && method_exists($assignable, 'notify')) {
            $assignable->notify(new FleetAssignedNotification($fleetAssignment, 'created'));
        } elseif ($assignable && $assignable instanceof \App\Models\RH\Team) {
            foreach ($assignable->employees as $employee) {
                $employee->notify(new FleetAssignedNotification($fleetAssignment, 'created'));
            }
        }
    }

    /**
     * Handle the FleetAssignment "updated" event.
     */
    public function updated(FleetAssignment $fleetAssignment): void
    {
        // Si les dates d'assignation ont changé, ou le véhicule, ou l'assignable
        // Note: 'updated' est appelé après 'updating', donc la validation a déjà eu lieu.
        if ($fleetAssignment->isDirty(['start_date', 'end_date', 'fleet_id', 'assignable_id', 'assignable_type'])) {
            $assignable = $fleetAssignment->assignable;

            if ($assignable && method_exists($assignable, 'notify')) {
                $assignable->notify(new FleetAssignedNotification($fleetAssignment, 'updated'));
            } elseif ($assignable && $assignable instanceof \App\Models\RH\Team) {
                foreach ($assignable->employees as $employee) {
                    $employee->notify(new FleetAssignedNotification($fleetAssignment, 'updated'));
                }
            }
        }
    }

    /**
     * Handle the FleetAssignment "saved" event.
     * This event is fired after a model is created or updated.
     */
    public function saved(FleetAssignment $fleetAssignment): void
    {
        // Logic to update the status based on dates
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

        // Only update if the status has actually changed to avoid infinite loops
        if ($newStatus && $fleetAssignment->status !== $newStatus) {
            $fleetAssignment->updateQuietly(['status' => $newStatus]);
        }
    }

    /**
     * Handle the FleetAssignment "deleted" event.
     */
    public function deleted(FleetAssignment $fleetAssignment): void
    {
        $assignable = $fleetAssignment->assignable;

        if ($assignable && method_exists($assignable, 'notify')) {
            $assignable->notify(new FleetAssignedNotification($fleetAssignment, 'deleted'));
        } elseif ($assignable && $assignable instanceof \App\Models\RH\Team) {
            foreach ($assignable->employees as $employee) {
                $employee->notify(new FleetAssignedNotification($fleetAssignment, 'deleted'));
            }
        }
    }
}
