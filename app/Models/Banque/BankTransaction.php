<?php

namespace App\Models\Banque;

use App\Observers\Banque\BankTransactionObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy([BankTransactionObserver::class])]
class BankTransaction extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function account(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    // Le paiement ERP associé (Rapprochement)
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'reconciled_at' => 'datetime',
            'amount' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }
}
