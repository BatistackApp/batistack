<?php

namespace App\Observers\Tiers;

use App\Models\Tiers\Tiers;

class TiersObserver
{
    public function saving(Tiers $tier): void
    {
        // Convention : Nom d'entreprise en majuscules
        if ($tier->nature === 'company' && $tier->isDirty('name')) {
            $tier->name = strtoupper($tier->name);
        }

        // Nettoyage basique des téléphones (garde uniquement chiffres et +)
        if ($tier->phone) {
            $tier->phone = preg_replace('/[^\d\+]/', '', $tier->phone);
        }
    }
}
