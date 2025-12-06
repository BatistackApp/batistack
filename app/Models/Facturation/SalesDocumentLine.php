<?php

namespace App\Models\Facturation;

use App\Enums\Facturation\SalesDocumentLineType;
use App\Models\Articles\Product;
use App\Observers\Facturation\SalesDocumentLineObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([SalesDocumentLineObserver::class])]
class SalesDocumentLine extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

    public function document(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected $casts = [
        'type' => SalesDocumentLineType::class,
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'discount_rate' => 'decimal:2',
        'buying_price' => 'decimal:2', // Pour la marge
    ];

    // --- Logique de Calcul (Accesseurs) ---

    /**
     * Montant HT de la ligne (avec remise appliquée)
     */
    public function getTotalHtAttribute(): float
    {
        if ($this->type !== SalesDocumentLineType::Product) {
            return 0;
        }

        $baseAmount = $this->quantity * $this->unit_price;
        $discountAmount = $baseAmount * ($this->discount_rate / 100);

        return round($baseAmount - $discountAmount, 2);
    }

    /**
     * Montant de la TVA de la ligne
     */
    public function getTotalVatAttribute(): float
    {
        return round($this->total_ht * ($this->vat_rate / 100), 2);
    }

    /**
     * Montant TTC de la ligne
     */
    public function getTotalTtcAttribute(): float
    {
        return $this->total_ht + $this->total_vat;
    }

    /**
     * Calcul de la marge brute sur la ligne
     * (Prix Vente HT - Prix Achat HT) * Qté
     */
    public function getMarginAttribute(): float
    {
        if ($this->type !== SalesDocumentLineType::Product) {
            return 0;
        }

        // Attention: la remise impacte la vente, pas l'achat
        $totalVenteNet = $this->total_ht;
        $totalAchat = $this->quantity * $this->buying_price;

        return round($totalVenteNet - $totalAchat, 2);
    }
}
