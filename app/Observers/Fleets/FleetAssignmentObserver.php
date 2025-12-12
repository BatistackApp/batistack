<?php

namespace App\Observers\Fleets;

use App\Enums\Fleets\FleetAssignmentStatus;
use App\Models\Fleets\FleetAssignment;
use App\Notifications\Fleets\FleetAssignedNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class FleetAssignmentObserver
{
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
