<?php

namespace App\Services\Core;

use App\Models\Core\Company;
use App\Models\Core\Plan;
use App\Models\Core\Subscription;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Crée un nouvel abonnement pour une entreprise.
     *
     * @param Company $company
     * @param Plan $plan
     * @return Subscription
     * @throws \Throwable
     */
    public function createSubscription(Company $company, Plan $plan): Subscription
    {
        // TODO: Gérer le cas où l'entreprise a déjà un abonnement actif.
        // Pour l'instant, on suppose que c'est une nouvelle inscription.

        return DB::transaction(function () use ($company, $plan) {
            // 1. Créer l'abonnement principal
            $subscription = $company->subscriptions()->create([
                'plan_id' => $plan->id,
                'status' => 'active', // Ou 'trialing' si on gère les essais
                'starts_at' => now(),
            ]);

            // 2. Ajouter les items basés sur les features du plan
            foreach ($plan->features as $feature) {
                $subscription->items()->create([
                    'subscribable_type' => get_class($feature),
                    'subscribable_id' => $feature->id,
                    'quantity' => $feature->pivot->value ?? 1, // Utilise la valeur du pivot si elle existe
                ]);
            }

            return $subscription;
        });
    }

    /**
     * Ajoute une option (feature) à un abonnement existant.
     *
     * @param Subscription $subscription
     * @param \App\Models\Core\Feature $feature
     * @param int $quantity
     * @return \App\Models\Core\SubscriptionItem
     */
    public function addFeatureToSubscription(Subscription $subscription, \App\Models\Core\Feature $feature, int $quantity = 1)
    {
        // TODO: Vérifier si la feature n'est pas déjà dans l'abonnement.

        return $subscription->items()->create([
            'subscribable_type' => get_class($feature),
            'subscribable_id' => $feature->id,
            'quantity' => $quantity,
        ]);
    }

    // TODO: Implémenter les méthodes changePlan() et cancelSubscription()
}
