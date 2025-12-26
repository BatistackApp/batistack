<?php

namespace App\Observers\RH;

use App\Enums\Tiers\TierNature;
use App\Models\RH\Employee;
use App\Models\Tiers\Tiers;

class EmployeeObserver
{
    /**
     * Handle the Employee "created" event.
     */
    public function created(Employee $employee): void
    {
        // Créer un Tiers correspondant pour cet employé, s'il n'en a pas déjà un
        // basé sur l'email, qui devrait être unique par compagnie.
        Tiers::firstOrCreate(
            [
                'company_id' => $employee->company_id,
                'email' => $employee->email,
            ],
            [
                'name' => $employee->full_name,
                'nature' => TierNature::Employee,
                'is_active' => $employee->is_active,
                'is_supplier' => true, // Un employé est un "fournisseur" de services interne
            ]
        );
    }

    /**
     * Handle the Employee "updated" event.
     */
    public function updated(Employee $employee): void
    {
        // Si des informations clés changent, on met à jour le Tiers lié
        if ($employee->isDirty('full_name', 'email', 'is_active')) {
            $tier = Tiers::where('company_id', $employee->company_id)
                ->where('email', $employee->getOriginal('email')) // On cherche par l'ancien email
                ->first();

            if ($tier) {
                $tier->update([
                    'name' => $employee->full_name,
                    'email' => $employee->email,
                    'is_active' => $employee->is_active,
                ]);
            }
        }
    }
}
