<?php

namespace App\Models\Banque;

use App\Enums\Banque\PaymentMethod;
use App\Enums\Banque\PaymentStatus;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use App\Observers\Banque\PaymentObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[ObservedBy([PaymentObserver::class])]
class Payment extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];
    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    // Ce que ce paiement règle (Facture ou Achat)
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BankTransaction::class);
    }

    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'date' => 'date',
            'amount' => 'decimal:2',
            'status' => PaymentStatus::class,
        ];
    }


    /**
     * Helper pour savoir si le paiement est un Encaissement (Client) ou un Décaissement (Fournisseur/Achat).
     * Dans l'état actuel, il faudrait vérifier le type de `payable()`.
     * Ex: Si payable est une 'Invoice', c'est un encaissement (montant positif pour le solde).
     * Si payable est une 'Purchase', c'est un décaissement (montant négatif pour le solde).
     * On va ajouter un attribut simple pour l'instant :
     */
    public function getIsIncomingAttribute(): bool
    {
        // Logique à affiner plus tard selon les modèles réels (Invoice/Purchase)
        // Supposons que les paiements Facture sont des encaissements.
        // On se base sur le champ 'payable_type' (ex: App\Models\Facturation\Invoice)
        return str_contains($this->payable_type ?? '', 'Invoice');
    }
}
