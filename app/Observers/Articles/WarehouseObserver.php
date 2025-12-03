<?php

namespace App\Observers\Articles;

use App\Models\Articles\Warehouse;

class WarehouseObserver
{
    public function creating(Warehouse $warehouse): void
    {
        // Si ce dépôt est marqué comme défaut, on enlève le flag sur les autres du même tenant
        if ($warehouse->is_default) {
            Warehouse::where('company_id', $warehouse->company_id)
                ->where('id', '!=', $warehouse->id)
                ->update(['is_default' => false]);
        }
    }

    public function saving(Warehouse $warehouse): void
    {
        // Si c'est le tout premier dépôt de l'entreprise, on le force par défaut
        if (Warehouse::where('company_id', $warehouse->company_id)->doesntExist()) {
            $warehouse->is_default = true;
        }
    }
}
