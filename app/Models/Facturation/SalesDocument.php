<?php

namespace App\Models\Facturation;

use App\Enums\Facturation\SalesDocumentLineType;
use App\Enums\Facturation\SalesDocumentStatus;
use App\Enums\Facturation\SalesDocumentType;
use App\Interfaces\Payable;
use App\Models\Chantiers\Chantiers;
use App\Models\Comptabilite\ComptaAccount;
use App\Models\Tiers\Tiers;
use App\Observers\Facturation\SalesDocumentObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([SalesDocumentObserver::class])]
class SalesDocument extends Model implements Payable
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $guarded = [];



    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function chantiers(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function lines(): HasMany
    {
        // On trie toujours par ordre visuel
        return $this->hasMany(SalesDocumentLine::class)->orderBy('sort_order');
    }

    protected function casts(): array
    {
        return [
            'type' => SalesDocumentType::class,
            'status' => SalesDocumentStatus::class,
            'is_posted_to_compta' => 'boolean',
            'date' => 'date',
            'validity_date' => 'date',
            'due_date' => 'date',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'margin_amount' => 'decimal:2',
        ];
    }

    // --- Logique Métier ---

    /**
     * Méthode centrale de recalcul.
     * À appeler après chaque modif de ligne.
     */
    public function recalculate(): void
    {
        // On charge les lignes pour éviter les requêtes N+1, mais sans cache
        $lines = $this->lines()->get();

        $ht = 0;
        $vat = 0;
        $cost = 0;

        foreach ($lines as $line) {
            // On utilise les accesseurs du modèle Line
            $ht += $line->total_ht;
            $vat += $line->total_vat;

            // Calcul du coût global (Qté * Prix Achat)
            if ($line->type === SalesDocumentLineType::Product) {
                $cost += ($line->quantity * $line->buying_price);
            }
        }

        $this->updateQuietly([
            'total_ht' => $ht,
            'total_vat' => $vat,
            'total_ttc' => $ht + $vat,
            'total_cost' => $cost,
            'margin_amount' => $ht - $cost,
        ]);
    }

    /**
     * Helper pour savoir si le document est verrouillé (non modifiable)
     */
    public function isLocked(): bool
    {
        // Une facture envoyée ou payée ne doit plus bouger
        // Un devis signé ne doit plus bouger
        if ($this->type === SalesDocumentType::Invoice) {
            return in_array($this->status, [SalesDocumentStatus::Sent, SalesDocumentStatus::Paid, SalesDocumentStatus::Partial]);
        }

        if ($this->type === SalesDocumentType::Quote) {
            return in_array($this->status, [SalesDocumentStatus::Accepted, SalesDocumentStatus::Refused]);
        }

        return false;
    }

    /**
     * Détermine si un paiement associé à ce document est un encaissement.
     *
     * @return bool
     */
    public function isIncomingPayment(): bool
    {
        // Un paiement sur une facture de vente est toujours un encaissement.
        return true;
    }

    /**
     * Récupère le compte comptable associé à ce type de document.
     *
     * @return ComptaAccount
     */
    public function getComptaAccount(): ComptaAccount
    {
        // Pour une vente, on mouvement le compte client (ex: 411xxx)
        return ComptaAccount::where('company_id', $this->company_id)
            ->where('number', 'like', '411%')
            ->firstOrFail(); // Simplification: on prend le premier compte client trouvé
    }
}
