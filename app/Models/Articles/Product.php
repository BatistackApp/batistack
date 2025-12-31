<?php

namespace App\Models\Articles;

use App\Enums\Articles\ProductType;
use App\Enums\Articles\ProductUnit;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use App\Observers\Articles\ProductObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([ProductObserver::class])]
class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;
    protected $guarded = [];

    public function mainSupplier(): BelongsTo
    {
        return $this->belongsTo(Tiers::class, 'main_supplier_id');
    }

    // Les Stocks (1 ligne par dépôt)
    public function stocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    // Relation Ouvrage : Les composants (Enfants) de ce produit
    // Exemple : Si ce produit est un "Mur", récupère "Ciment", "Brique"
    public function children(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_assemblies', 'parent_product_id', 'child_product_id')
            ->using(ProductAssembly::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    // Relation Inverse : Dans quels ouvrages ce produit est-il utilisé ?
    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_assemblies', 'child_product_id', 'parent_product_id')
            ->using(ProductAssembly::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'unit' => ProductUnit::class,
            'buying_price' => 'decimal:2',
            'selling_price' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'is_stockable' => 'boolean',
            'is_active' => 'boolean',
            'manufacturing_duration' => 'integer',
        ];
    }

    // --- Logique Métier ---

    /**
     * Calcule le coût de revient théorique si c'est un Ouvrage.
     * C'est une méthode lourde, à ne pas utiliser dans une boucle foreach de 1000 produits.
     */
    public function calculateAssemblyCost(): float
    {
        if ($this->type !== ProductType::Assembly) {
            return $this->buying_price;
        }

        // On charge les enfants pour faire le calcul
        // Note: Pour un système très complexe, on gérerait la récursivité sur N niveaux,
        // mais pour commencer, 1 niveau de profondeur est souvent suffisant en BTP simple.
        $cost = 0;
        foreach ($this->children as $child) {
            $qty = $child->pivot->quantity;
            $childCost = $child->buying_price; // Ou $child->calculateAssemblyCost() pour récursif
            $cost += ($childCost * $qty);
        }

        return $cost;
    }

    /**
     * Récupère le stock global (tous dépôts confondus)
     */
    public function getTotalStockAttribute()
    {
        return $this->stocks()->sum('quantity_on_hand');
    }
}
