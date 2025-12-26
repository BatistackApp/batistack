<?php

namespace App\Services\Reporting;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Facturation\PurchaseDocument;
use App\Models\Facturation\SalesDocument;
use App\Models\Fleets\FleetAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected Company $company
    ) {}

    /**
     * Récupère les chantiers triés par marge réelle (Top ou Flop).
     *
     * @param int $limit Nombre de chantiers à retourner
     * @param string $order 'desc' pour les plus rentables, 'asc' pour les moins rentables
     * @return Collection
     */
    public function getChantiersRentability(int $limit = 5, string $order = 'desc'): Collection
    {
        // On récupère les chantiers actifs ou terminés récemment
        // Le calcul de la marge se fait via les accesseurs du modèle, donc on récupère les modèles
        // Attention : pour de gros volumes, il faudrait passer par une requête SQL brute ou une vue matérialisée

        return Chantiers::forCompany($this->company)
            ->get() // On charge en mémoire pour utiliser les accesseurs (optimisation possible plus tard)
            ->sortBy([
                ['real_margin', $order]
            ])
            ->take($limit)
            ->map(function ($chantier) {
                return [
                    'id' => $chantier->id,
                    'name' => $chantier->name,
                    'revenue' => $chantier->total_sales_revenue,
                    'cost' => $chantier->total_real_cost,
                    'margin' => $chantier->real_margin,
                    'margin_percent' => $chantier->total_sales_revenue > 0
                        ? ($chantier->real_margin / $chantier->total_sales_revenue) * 100
                        : 0,
                ];
            });
    }

    /**
     * Agrège les alertes financières critiques.
     *
     * @return array
     */
    public function getFinancialAlerts(): array
    {
        // Factures clients en retard
        $overdueInvoices = SalesDocument::forCompany($this->company)
            ->where('type', SalesDocumentType::Invoice)
            ->whereIn('status', [SalesDocumentStatus::Sent, SalesDocumentStatus::Partial])
            ->where('due_date', '<', now())
            ->sum(DB::raw('total_ttc - amount_paid'));

        // Dettes fournisseurs à payer dans les 7 jours
        $upcomingDebts = PurchaseDocument::forCompany($this->company)
            ->whereIn('status', [PurchaseDocumentStatus::Received, PurchaseDocumentStatus::Approved, PurchaseDocumentStatus::Partial])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->sum(DB::raw('total_ttc - amount_paid'));

        // Dettes fournisseurs en retard
        $overdueDebts = PurchaseDocument::forCompany($this->company)
            ->whereIn('status', [PurchaseDocumentStatus::Received, PurchaseDocumentStatus::Approved, PurchaseDocumentStatus::Partial])
            ->where('due_date', '<', now())
            ->sum(DB::raw('total_ttc - amount_paid'));

        return [
            'overdue_invoices_amount' => $overdueInvoices,
            'upcoming_debts_amount' => $upcomingDebts,
            'overdue_debts_amount' => $overdueDebts,
        ];
    }

    /**
     * Calcule le taux d'utilisation de la flotte sur les 30 derniers jours.
     *
     * @return float Pourcentage d'utilisation
     */
    public function getFleetUtilization(): float
    {
        $totalVehicles = \App\Models\Fleets\Fleet::forCompany($this->company)
            ->where('is_available', true) // On ne compte que les véhicules censés être dispos
            ->count();

        if ($totalVehicles === 0) {
            return 0.0;
        }

        $startDate = now()->subDays(30);
        $endDate = now();
        $totalDaysPossible = $totalVehicles * 30;

        // On récupère toutes les assignations qui chevauchent la période de 30 jours
        $assignments = FleetAssignment::forCompany($this->company)
            ->where('start_date', '<', $endDate)
            ->where(function ($query) use ($startDate) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>', $startDate);
            })
            ->get();

        $totalAssignedDays = $assignments->sum(function ($assignment) use ($startDate, $endDate) {
            // On calcule l'intersection entre la période d'assignation et la fenêtre de 30 jours
            $assignStart = $assignment->start_date->greaterThan($startDate) ? $assignment->start_date : $startDate;
            $effectiveEndDate = $assignment->end_date ?? $endDate;
            $assignEnd = $effectiveEndDate->lessThan($endDate) ? $effectiveEndDate : $endDate;

            // On calcule la différence en jours
            return $assignStart->diffInDays($assignEnd) + 1; // +1 pour inclure le jour de début
        });

        if ($totalDaysPossible === 0) {
            return 0.0;
        }

        return ($totalAssignedDays / $totalDaysPossible) * 100;
    }
}
