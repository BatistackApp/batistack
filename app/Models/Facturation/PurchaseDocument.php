<?php

namespace App\Models\Facturation;

use App\Enums\Facturation\PurchaseDocumentStatus;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use App\Observers\Facturation\PurchaseDocumentObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy([PurchaseDocumentObserver::class])]
class PurchaseDocument extends Model
{
    use SoftDeletes, BelongsToCompany;

    protected $guarded = [];

    public function tiers(): BelongsTo
    {
        return $this->belongsTo(Tiers::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function chantiers(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseDocumentLine::class);
    }

    protected function casts(): array
    {
        return [
            'document_date' => 'date',
            'due_date' => 'date',
            'status' => PurchaseDocumentStatus::class,
            'is_posted_to_compta' => 'boolean',
            'total_ht' => 'decimal:2',
            'total_vat' => 'decimal:2',
            'total_ttc' => 'decimal:2',
        ];
    }

    // --- Logique Métier ---

    /**
     * Méthode centrale de recalcul des totaux.
     */
    public function recalculate(): void
    {
        $lines = $this->lines()->get();

        $total_ht = $lines->sum('total_ht');
        $total_vat = $lines->sum('total_vat');

        $this->updateQuietly([
            'total_ht' => $total_ht,
            'total_vat' => $total_vat,
            'total_ttc' => $total_ht + $total_vat,
        ]);
    }

    /**
     * Helper pour savoir si le document est verrouillé (non modifiable)
     */
    public function isLocked(): bool
    {
        // Une facture payée ne doit plus bouger
        return in_array($this->status, [PurchaseDocumentStatus::Paid, PurchaseDocumentStatus::Partial]);
    }
}
