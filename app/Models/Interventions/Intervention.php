<?php

namespace App\Models\Interventions;

use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Enums\Interventions\InterventionBillingType;
use App\Enums\Interventions\InterventionStatus;
use App\Models\Articles\Product;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Facturation\SalesDocument;
use App\Models\RH\Employee;
use App\Models\RH\Timesheet;
use App\Models\Tiers\Tiers;
use App\Observers\Interventions\InterventionObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([InterventionObserver::class])]
class Intervention extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => InterventionStatus::class,
            'billing_type' => InterventionBillingType::class,
            'is_billable' => 'boolean',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'actual_start_date' => 'date',
            'actual_end_date' => 'date',
            'total_labor_cost' => 'decimal:2',
            'total_material_cost' => 'decimal:2',
            'fixed_price_amount' => 'decimal:2',
            'costs_posted_to_compta' => 'boolean',
            'target_margin_rate' => 'decimal:2',
            'actual_margin' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Tiers::class, 'client_id');
    }

    public function technician(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'technician_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'intervention_product')
            ->using(InterventionProduct::class) // Utilisation du modèle de pivot
            ->withPivot('quantity');
    }

    public function salesDocument(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class);
    }

    /**
     * Recalcule le coût total de la main-d'œuvre pour cette Intervention.
     */
    public function recalculateLaborCost(): void
    {
        $totalCost = $this->timesheets->sum(function (Timesheet $timesheet) {
            return $timesheet->cost;
        });

        $this->updateQuietly(['total_labor_cost' => $totalCost]);
    }

    /**
     * Recalcule le coût total des matériaux pour cette Intervention.
     */
    public function recalculateMaterialCost(): void
    {
        $totalCost = $this->products->sum(function (Product $product) {
            return $product->pivot->quantity * ($product->buying_price ?? 0);
        });

        $this->updateQuietly(['total_material_cost' => $totalCost]);
    }

    /**
     * Génère une facture à partir de l'intervention.
     */
    public function generateSalesDocument(): ?SalesDocument
    {
        // Si non facturable ou déjà facturé, on arrête
        if (!$this->is_billable || $this->billing_type === InterventionBillingType::NonBillable || $this->sales_document_id) {
            return null;
        }

        // Récupérer la marge depuis l'intervention ou les paramètres de l'entreprise
        $marginPercentage = $this->target_margin_rate ?? $this->company->default_intervention_margin ?? 20.00;
        $marginRate = 1 + ($marginPercentage / 100);

        $salesDocument = SalesDocument::create([
            'company_id' => $this->company_id,
            'tiers_id' => $this->client_id,
            'chantiers_id' => $this->chantier_id,
            'type' => SalesDocumentType::Invoice,
            'status' => SalesDocumentStatus::Draft,
            'date' => now(),
            'due_date' => now()->addDays(30),
            'reference' => "FAC-INT-{$this->id}",
            'sourceable_type' => self::class,
            'sourceable_id' => $this->id,
        ]);

        if ($this->billing_type === InterventionBillingType::FixedPrice) {
            // Facturation au forfait
            $salesDocument->lines()->create([
                'description' => "Forfait Intervention #{$this->id}",
                'quantity' => 1,
                'unit_price' => $this->fixed_price_amount,
                'vat_rate' => 20.00,
            ]);

            // Calcul de la marge réelle
            $totalCost = $this->total_labor_cost + $this->total_material_cost;
            $this->updateQuietly(['actual_margin' => $this->fixed_price_amount - $totalCost]);

        } else {
            // Facturation en Régie (Time & Material)

            // Ajouter les lignes de main-d'œuvre
            if ($this->total_labor_cost > 0) {
                $salesDocument->lines()->create([
                    'description' => 'Main d\'œuvre',
                    'quantity' => 1,
                    'unit_price' => $this->total_labor_cost * $marginRate,
                    'vat_rate' => 20.00,
                ]);
            }

            // Ajouter les lignes de matériaux
            foreach ($this->products as $product) {
                $sellingPrice = $product->selling_price > 0 ? $product->selling_price : ($product->buying_price * $marginRate);
                $salesDocument->lines()->create([
                    'product_id' => $product->id,
                    'description' => $product->name,
                    'quantity' => $product->pivot->quantity,
                    'unit_price' => $sellingPrice,
                    'vat_rate' => 20.00,
                ]);
            }

            // Recalculer le total de la facture pour déduire la marge réelle
            $salesDocument->recalculate();
            $totalBilled = $salesDocument->total_ht;
            $totalCost = $this->total_labor_cost + $this->total_material_cost;
            $this->updateQuietly(['actual_margin' => $totalBilled - $totalCost]);
        }

        $salesDocument->recalculate();

        $this->update(['sales_document_id' => $salesDocument->id]);

        return $salesDocument;
    }
}
