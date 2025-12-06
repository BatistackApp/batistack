<?php

namespace App\Models\Banque;

use App\Enums\Banque\PaymentMethod;
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
        ];
    }
}
