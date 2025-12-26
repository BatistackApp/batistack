<?php

namespace App\Models\Banque;

use App\Enums\Banque\BankAccountType;
use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class BankAccount extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    /**
     * Les transactions bancaires (Relevé)
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class)->orderByDesc('date');
    }

    /**
     * Les paiements internes enregistrés sur ce compte
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('date');
    }

    protected function casts(): array
    {
        return [
            'type' => BankAccountType::class, // Cast vers l'Enum
            'current_balance' => 'decimal:2',
            'last_synced_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // --- Helpers / BridgeAPI ---

    /**
     * Vérifie si le compte est connecté à BridgeAPI
     */
    public function isConnectedToBridge(): bool
    {
        return !empty($this->bridge_account_id);
    }

    /**
     * Met à jour le solde de manière atomique.
     *
     * @param float $amountToAdd Montant à ajouter (peut être négatif pour soustraire).
     */
    public function updateBalance(float $amountToAdd): void
    {
        if ($amountToAdd == 0) {
            return;
        }

        // Utilisation d'une requête raw pour garantir l'atomicité et éviter les race conditions.
        DB::table('bank_accounts')
            ->where('id', $this->id)
            ->update(['current_balance' => DB::raw("current_balance + {$amountToAdd}")]);

        // On rafraîchit le modèle pour que l'instance actuelle ait le nouveau solde.
        $this->refresh();

        if ($this->type !== BankAccountType::Bank) {
            $this->touch('last_synced_at');
        }
    }
}
