<?php

namespace App\Jobs\Fleets;

use App\Models\Chantiers\Chantiers;
use App\Models\Fleets\Fleet;
use App\Models\RH\Employee;
use App\Models\RH\Team;
use App\Models\RH\Timesheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AllocateFleetCostsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ?\DateTimeInterface $date = null
    ) {
        // Par défaut, on traite la journée d'hier (pour être sûr d'avoir tous les pointages)
        // ou la date passée en paramètre.
        $this->date = $date ?? now()->subDay();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $targetDate = \Carbon\Carbon::instance($this->date)->startOfDay();
        Log::info("Début de l'imputation des coûts flotte pour la date du {$targetDate->format('Y-m-d')}");

        // 1. Récupérer tous les véhicules actifs avec un coût journalier défini
        $fleets = Fleet::where('internal_daily_cost', '>', 0)->get();

        foreach ($fleets as $fleet) {
            // 2. Trouver l'assignation active pour cette date
            $assignment = $fleet->assignments()
                ->where('start_date', '<=', $targetDate)
                ->where(function ($query) use ($targetDate) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', $targetDate);
                })
                ->first();

            if (!$assignment) {
                continue;
            }

            $chantierId = null;

            // 3. Déterminer le chantier associé via l'assignataire (Employé ou Équipe)
            if ($assignment->assignable_type === Employee::class) {
                // Si assigné à un employé, on regarde où il a pointé ce jour-là
                // On prend le chantier où il a passé le plus de temps
                $timesheet = Timesheet::where('employee_id', $assignment->assignable_id)
                    ->whereDate('date', $targetDate)
                    ->whereNotNull('chantier_id')
                    ->orderByDesc('hours')
                    ->first();

                if ($timesheet) {
                    $chantierId = $timesheet->chantier_id;
                }

            } elseif ($assignment->assignable_type === Team::class) {
                // Si assigné à une équipe, on regarde où le chef d'équipe (ou un membre) a pointé
                // Simplification : on cherche un pointage de n'importe quel membre de l'équipe
                $team = Team::find($assignment->assignable_id);
                if ($team) {
                    // On suppose une relation employees() sur Team, ou on passe par une table pivot
                    // Ici, on va chercher les employés de l'équipe via la table employees (team_id)
                    $employeeIds = Employee::where('team_id', $team->id)->pluck('id');

                    $timesheet = Timesheet::whereIn('employee_id', $employeeIds)
                        ->whereDate('date', $targetDate)
                        ->whereNotNull('chantier_id')
                        ->orderByDesc('hours')
                        ->first();

                    if ($timesheet) {
                        $chantierId = $timesheet->chantier_id;
                    }
                }
            }

            // 4. Imputer le coût au chantier
            if ($chantierId) {
                try {
                    $chantier = Chantiers::find($chantierId);
                    if ($chantier) {
                        $chantier->increment('total_fleet_cost', $fleet->internal_daily_cost);
                        Log::info("Coût véhicule {$fleet->name} ({$fleet->internal_daily_cost}€) imputé au chantier #{$chantier->id}");
                    }
                } catch (\Exception $e) {
                    Log::error("Erreur lors de l'imputation du coût flotte : " . $e->getMessage());
                }
            } else {
                // Optionnel : Logguer que le véhicule était assigné mais sans chantier identifié (Coût de structure ?)
                // Log::warning("Véhicule {$fleet->name} assigné mais aucun chantier trouvé pour la date du {$targetDate->format('Y-m-d')}");
            }
        }

        Log::info("Fin de l'imputation des coûts flotte.");
    }
}
