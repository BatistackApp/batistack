<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class CheckQuota
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $featureCode, string $modelClass = null): Response
    {
        $company = $request->user()?->company;

        if (!$company) {
            return $next($request);
        }

        // Calcul de l'usage actuel
        $currentUsage = 0;
        if ($modelClass && class_exists($modelClass)) {
            // Si un modèle est fourni, on compte le nombre d'enregistrements pour cette compagnie
            // On suppose que le modèle a un scope ou une relation company, ou utilise le trait BelongsToCompany
            // Mais attention, BelongsToCompany applique un global scope.
            // Donc Model::count() retournera le count pour la company courante.
            $currentUsage = $modelClass::count();
        } else {
            // Si pas de modèle, on ne peut pas calculer l'usage automatiquement ici.
            // Ce middleware est donc surtout utile pour les cas simples (Count de modèles).
            // Pour des cas plus complexes, il faudra passer l'usage ou utiliser une autre méthode.
        }

        if (!$company->checkQuota($featureCode, $currentUsage)) {

            // Si c'est une requête Filament/Livewire, on veut peut-être envoyer une notification
            if ($request->expectsJson() || $request->is('livewire/*')) {
                 Notification::make()
                    ->title('Limite atteinte')
                    ->body("Vous avez atteint la limite autorisée pour votre abonnement ({$featureCode}).")
                    ->danger()
                    ->send();

                 // On peut arrêter là ou laisser continuer (mais l'action échouera probablement si on bloque au niveau controller)
                 // Pour un middleware bloquant :
                 abort(403, "Quota atteint pour {$featureCode}");
            }

            abort(403, "Quota atteint pour {$featureCode}");
        }

        return $next($request);
    }
}
