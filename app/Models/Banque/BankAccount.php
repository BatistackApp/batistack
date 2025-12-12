<?php

namespace App\Models\Banque;

use App\Enums\Banque\BankAccountType;
use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     * Permet de mettre à jour le solde (appelé lors d'une synchro ou d'un pointage)
     */
    public function updateBalance(float $newBalance): void
    {
        if ($newBalance > 0) {
            $this->increment('current_balance', $newBalance);
        } elseif ($newBalance < 0) {
            $this->decrement('current_balance', $newBalance);
        }

        if ($this->type !== BankAccountType::Bank) {
            $this->touch('last_synced_at');
        }
    }
}
