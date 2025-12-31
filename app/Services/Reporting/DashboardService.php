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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    // Durées de cache en secondes
    const CACHE_TTL_RENTABILITY = 3600; // 1 heure
    const CACHE_TTL_ALERTS = 1800;      // 30 minutes
    const CACHE_TTL_FLEET = 21600;      // 6 heures

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
        $cacheKey = "dashboard_rentability_{$this->company->id}_{$limit}_{$order}";

        return Cache::remember($cacheKey, self::CACHE_TTL_RENTABILITY, function () use ($limit, $order) {
            return Chantiers::forCompany($this->company)
                ->withRealMargin() // Utilisation du scope pour le calcul en DB
                ->orderBy('real_margin', $order)
                ->limit($limit)
                ->get()
                ->map(function ($chantier) {
                    // Les accesseurs peuvent toujours être utilisés pour les calculs finaux
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
        });
    }

    /**
     * Agrège les alertes financières critiques.
     *
     * @return array
     */
    public function getFinancialAlerts(): array
    {
        $cacheKey = "dashboard_alerts_{$this->company->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_ALERTS, function () {
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
        });
    }

    /**
     * Calcule le taux d'utilisation de la flotte sur les 30 derniers jours.
     *
     * @return float Pourcentage d'utilisation
     */
    public function getFleetUtilization(): float
    {
        $cacheKey = "dashboard_fleet_utilization_{$this->company->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL_FLEET, function () {
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

                if ($assignStart->greaterThan($assignEnd)) {
                    return 0;
                }

                // On calcule la différence en jours
                return $assignStart->diffInDays($assignEnd) + 1; // +1 pour inclure le jour de début
            });

            if ($totalDaysPossible === 0) {
                return 0.0;
            }

            return ($totalAssignedDays / $totalDaysPossible) * 100;
        });
    }

    /**
     * Invalide le cache pour une entreprise donnée.
     * À appeler lors de modifications majeures (ex: import de données, clôture mensuelle).
     */
    public function clearCache(): void
    {
        // On ne peut pas facilement supprimer par wildcard avec le driver 'file' par défaut.
        // Mais on peut supprimer les clés connues.
        Cache::forget("dashboard_rentability_{$this->company->id}_5_desc");
        Cache::forget("dashboard_rentability_{$this->company->id}_5_asc");
        Cache::forget("dashboard_alerts_{$this->company->id}");
        Cache::forget("dashboard_fleet_utilization_{$this->company->id}");

        // Pour une gestion plus fine, on pourrait utiliser des tags de cache (Redis/Memcached uniquement)
        // Cache::tags(['dashboard', "company:{$this->company->id}"])->flush();
    }
}
