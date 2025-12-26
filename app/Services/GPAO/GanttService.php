<?php

namespace App\Services\GPAO;

use App\Models\GPAO\ProductionOrder;
use Illuminate\Support\Collection;

class GanttService
{
    /**
     * Prépare les données des OF pour un affichage en diagramme de Gantt.
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Collection
     */
    public function getGanttData(\DateTimeInterface $startDate, \DateTimeInterface $endDate): Collection
    {
        $query = ProductionOrder::query()
            ->whereBetween('planned_start_date', [$startDate, $endDate])
            ->orWhereBetween('planned_end_date', [$startDate, $endDate]);

        return $query->get()->map(function (ProductionOrder $of) {
            return [
                'id' => $of->id,
                'text' => $of->reference . ' - ' . $of->product->name,
                'start_date' => $of->planned_start_date->format('Y-m-d'),
                'end_date' => $of->planned_end_date->format('Y-m-d'),
                'progress' => $this->calculateProgress($of),
                // 'dependencies' => $this->findDependencies($of), // Logique de dépendance à implémenter
            ];
        });
    }

    private function calculateProgress(ProductionOrder $of): float
    {
        // Simplification : le progrès est 0, 50% ou 100%
        return match ($of->status) {
            'completed' => 1,
            'in_progress' => 0.5,
            default => 0,
        };
    }
}
