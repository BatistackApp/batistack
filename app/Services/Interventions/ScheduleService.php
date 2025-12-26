<?php

namespace App\Services\Interventions;

use App\Models\Interventions\Intervention;
use Illuminate\Support\Collection;

class ScheduleService
{
    /**
     * Prépare les données des interventions pour un affichage calendrier (type FullCalendar).
     *
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @param int|null $technicianId
     * @return Collection
     */
    public function getCalendarEvents(\DateTimeInterface $startDate, \DateTimeInterface $endDate, ?int $technicianId = null): Collection
    {
        $query = Intervention::query()
            ->whereBetween('planned_start_date', [$startDate, $endDate])
            ->orWhereBetween('planned_end_date', [$startDate, $endDate]);

        if ($technicianId) {
            $query->where('technician_id', $technicianId);
        }

        return $query->get()->map(function (Intervention $intervention) {
            return [
                'id' => $intervention->id,
                'title' => $intervention->title,
                'start' => $intervention->planned_start_date->format('Y-m-d'),
                'end' => $intervention->planned_end_date?->format('Y-m-d'),
                'color' => $this->getColorForStatus($intervention->status),
                'extendedProps' => [
                    'client' => $intervention->client->name,
                    'technician' => $intervention->technician?->full_name,
                ]
            ];
        });
    }

    private function getColorForStatus($status): string
    {
        return match ($status) {
            'planned' => '#3498db',
            'in_progress' => '#f1c40f',
            'completed' => '#2ecc71',
            'cancelled' => '#e74c3c',
            default => '#95a5a6',
        };
    }
}
