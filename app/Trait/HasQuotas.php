<?php

namespace App\Trait;

use App\Models\Core\Company;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

trait HasQuotas
{
    /**
     * Vérifie un quota et notifie/bloque si dépassé.
     *
     * @param string $featureCode Le code de la fonctionnalité (ex: 'limit_users')
     * @param int|null $currentUsage L'usage actuel (si null, on essaiera de le deviner ou on passera 0)
     * @param bool $abort Si true, lance une exception 403. Sinon retourne false.
     * @return bool
     */
    protected function checkQuota(string $featureCode, ?int $currentUsage = null, bool $abort = true): bool
    {
        /** @var Company $company */
        $company = Auth::user()?->company;

        if (!$company) {
            return true; // Pas de company, pas de quota (ou admin global)
        }

        // Si l'usage n'est pas fourni, on ne peut pas vérifier grand chose sauf si on a une logique par défaut
        // Ici on suppose que l'appelant fournit l'usage
        if ($currentUsage === null) {
            $currentUsage = 0;
        }

        if (!$company->checkQuota($featureCode, $currentUsage)) {

            Notification::make()
                ->title('Limite atteinte')
                ->body("Votre abonnement ne permet pas de dépasser la limite pour : {$featureCode}.")
                ->danger()
                ->send();

            if ($abort) {
                $this->halt(); // Pour Filament
            }

            return false;
        }

        return true;
    }
}
