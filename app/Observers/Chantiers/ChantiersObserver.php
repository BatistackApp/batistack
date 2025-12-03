<?php

namespace App\Observers\Chantiers;

use App\Jobs\Chantiers\GeocodeChantiersAddressJob;
use App\Models\Chantiers\Chantiers;

class ChantiersObserver
{
    public function creating(Chantiers $chantiers): void
    {
        if (empty($chantiers->reference)) {
            $year = now()->format('Y');

            // Compte le nombre de chantiers de cette année pour cette entreprise + 1
            // Note: C'est une méthode simple. Pour une haute concurrence, on utiliserait un verrou atomique (Atomic Lock),
            // mais pour un SaaS BTP standard, cela suffit amplement.
            $count = Chantiers::where('company_id', $chantiers->company_id)
                ->whereYear('created_at', $year)
                ->withTrashed()
                ->count();

            $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT); // 0001

            $chantiers->reference = "CH-{$year}-{$sequence}";
        }
    }

    public function saving(Chantiers $chantiers): void
    {
        // Si le statut passe à "Completed" et qu'il n'y a pas de date de fin réelle, on met la date du jour
        if ($chantiers->isDirty('status') && $chantiers->status->value === 'completed' && is_null($chantiers->end_date_real)) {
            $chantiers->end_date_real = now();
        }
    }

    public function saved(Chantiers $chantiers): void
    {
        // Si l'une des lignes d'adresse a changé
        if ($chantiers->wasChanged(['address', 'code_postal', 'ville'])) {
            GeocodeChantiersAddressJob::dispatch($chantiers);
        }
    }
}
