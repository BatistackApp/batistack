<?php

namespace App\Http\Middleware;

use App\Trait\BelongsToCompany;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Filament\Notifications\Notification;

class CheckQuota
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $featureCode  The feature code to check (e.g., 'limit_users')
     * @param  string|null  $modelClass  The model class to count usage against. MUST use App\Trait\BelongsToCompany.
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $featureCode, string $modelClass = null): Response
    {
        $company = $request->user()?->company;

        if (!$company) {
            return $next($request);
        }

        // Calcul de l'usage actuel
        $currentUsage = 0;

        if ($modelClass) {
            if (!class_exists($modelClass)) {
                Log::error("CheckQuota Middleware: Model class '{$modelClass}' not found.");
                abort(500, "Server Configuration Error: Invalid model for quota check.");
            }

            // Runtime Validation: Ensure the model enforces tenant scoping
            // We check if the model uses the BelongsToCompany trait to ensure Model::count() is scoped.
            $traits = class_uses_recursive($modelClass);

            if (!in_array(BelongsToCompany::class, $traits)) {
                Log::critical("CheckQuota Middleware Security Alert: Model '{$modelClass}' does not use BelongsToCompany trait. Automatic counting aborted to prevent data leak.");
                abort(500, "Server Configuration Error: Model does not support tenant scoping.");
            }

            // Safe to count: The global scope from the trait will apply
            $currentUsage = $modelClass::count();
        }

        if (!$company->checkQuota($featureCode, $currentUsage)) {

            // Si c'est une requête Filament/Livewire, on veut peut-être envoyer une notification
            if ($request->expectsJson() || $request->is('livewire/*')) {
                 Notification::make()
                    ->title('Limite atteinte')
                    ->body("Vous avez atteint la limite autorisée pour votre abonnement ({$featureCode}).")
                    ->danger()
                    ->send();
            }

            abort(403, "Quota atteint pour {$featureCode}");
        }

        return $next($request);
    }
}
