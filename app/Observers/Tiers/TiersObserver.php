<?php

namespace App\Observers\Tiers;

use App\Models\Tiers\Tiers;
use Illuminate\Validation\ValidationException;

class TiersObserver
{
    /**
     * @throws ValidationException
     */
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

        // Validation du numéro de SIRET (14 chiffres) si le pays est la France
        if ($tier->pays === 'FR' && $tier->siret_number) {
            $siret = preg_replace('/\s+/', '', $tier->siret_number);
            if (!preg_match('/^\d{14}$/', $siret)) {
                throw ValidationException::withMessages([
                    'siret_number' => 'Le numéro de SIRET doit être composé de 14 chiffres.'
                ]);
            }
            $tier->siret_number = $siret;
        }

        // Validation simple du numéro de TVA (commence par 2 lettres)
        if ($tier->vat_number) {
            $vat = preg_replace('/\s+/', '', strtoupper($tier->vat_number));
            if (!preg_match('/^[A-Z]{2}[A-Z0-9]{2,12}$/', $vat)) {
                 throw ValidationException::withMessages([
                    'vat_number' => 'Le format du numéro de TVA est invalide.'
                ]);
            }
            $tier->vat_number = $vat;
        }
    }
}
