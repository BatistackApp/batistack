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

        return Chantiers::where('company_id', $this->company->id)
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
        $overdueInvoices = SalesDocument::where('company_id', $this->company->id)
            ->where('type', SalesDocumentType::Invoice)
            ->whereIn('status', [SalesDocumentStatus::Sent, SalesDocumentStatus::Partial])
            ->where('due_date', '<', now())
            ->sum(DB::raw('total_ttc - amount_paid'));

        // Dettes fournisseurs à payer dans les 7 jours
        $upcomingDebts = PurchaseDocument::where('company_id', $this->company->id)
            ->whereIn('status', [PurchaseDocumentStatus::Received, PurchaseDocumentStatus::Approved, PurchaseDocumentStatus::Partial])
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->sum(DB::raw('total_ttc - amount_paid'));

        // Dettes fournisseurs en retard
        $overdueDebts = PurchaseDocument::where('company_id', $this->company->id)
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
        $totalVehicles = \App\Models\Fleets\Fleet::where('company_id', $this->company->id)
            ->where('is_available', true) // On ne compte que les véhicules censés être dispos
            ->count();

        if ($totalVehicles === 0) {
            return 0.0;
        }

        // Nombre de jours d'assignation sur les 30 derniers jours
        $startDate = now()->subDays(30);
        $endDate = now();
        $totalDaysPossible = $totalVehicles * 30;

        // C'est une approximation. Pour être précis, il faudrait sommer les jours d'intersection
        // entre chaque assignation et la période [J-30, J].
        // Simplification : on compte les véhicules assignés aujourd'hui

        $assignedVehiclesCount = FleetAssignment::where('company_id', $this->company->id)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->distinct('fleet_id')
            ->count('fleet_id');

        return ($assignedVehiclesCount / $totalVehicles) * 100;
    }
}
