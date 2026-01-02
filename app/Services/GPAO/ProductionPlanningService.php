<?php

namespace App\Services\GPAO;

use App\Enums\GPAO\ProductionOrderStatus;
use App\Models\GPAO\ProductionOrder;
use Carbon\Carbon;

class ProductionPlanningService
{
    /**
     * Planifie automatiquement un Ordre de Fabrication.
     *
     * @param ProductionOrder $order
     * @return ProductionOrder
     */
    public function schedule(ProductionOrder $order): ProductionOrder
    {
        // 1. Calcul de la durée totale en minutes
        $unitDuration = $order->product->manufacturing_duration ?? 0;

        if ($unitDuration <= 0) {
            // Si pas de durée définie, on met une durée par défaut (ex: 1h par unité) ou on ne planifie pas
            $unitDuration = 60;
        }

        $totalDurationMinutes = $unitDuration * $order->quantity;

        // 2. Trouver la date de début
        // On cherche le dernier OF planifié pour la même ressource (si assignée) ou globalement
        $query = ProductionOrder::query()
            ->where('company_id', $order->company_id)
            ->where('id', '!=', $order->id) // Exclure soi-même
            ->whereIn('status', [ProductionOrderStatus::Planned, ProductionOrderStatus::InProgress])
            ->whereNotNull('planned_end_date');

        if ($order->assigned_to_id && $order->assigned_to_type) {
            $query->where('assigned_to_id', $order->assigned_to_id)
                  ->where('assigned_to_type', $order->assigned_to_type);
        }

        $lastOrder = $query->orderBy('planned_end_date', 'desc')->first();

        if ($lastOrder) {
            // On commence après le dernier OF + 15 min de battement
            $startDate = Carbon::parse($lastOrder->planned_end_date)->addMinutes(15);
        } else {
            // Sinon on commence demain à 8h00
            $startDate = Carbon::tomorrow()->setHour(8)->setMinute(0)->setSecond(0);
        }

        // Si la date de début calculée est dans le passé (ex: dernier OF fini hier), on prend maintenant ou demain
        if ($startDate->isPast()) {
            $startDate = Carbon::now()->addHour(); // Dans 1h
            // Si c'est le soir (> 17h), on reporte au lendemain
            if ($startDate->hour >= 17) {
                $startDate = Carbon::tomorrow()->setHour(8)->setMinute(0);
            }
        }

        // 3. Calcul de la date de fin
        // Pour faire simple, on ajoute la durée brute.
        // Une amélioration future serait de ne compter que les heures ouvrées (ex: 8h-12h, 13h-17h).
        // Ici, si ça dépasse la journée, ça finira la nuit, ce qui est une approximation acceptable pour une V1.

        // Amélioration simple : Si ça dépasse 8h de travail, on étale sur plusieurs jours ouvrés ?
        // Pour l'instant, restons sur une durée continue pour ne pas complexifier le code sans tests.
        $endDate = (clone $startDate)->addMinutes($totalDurationMinutes);

        // 4. Mise à jour de l'OF
        $order->update([
            'planned_start_date' => $startDate,
            'planned_end_date' => $endDate,
            'status' => ProductionOrderStatus::Planned, // On passe en statut Planifié
        ]);

        return $order;
    }
}
