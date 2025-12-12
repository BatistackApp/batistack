<?php

namespace App\Models\Comptabilite;

use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ComptaEntry extends Model
{
    use HasFactory, BelongsToCompany;

    public function journal(): BelongsTo
    {
        return $this->belongsTo(ComptaJournal::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ComptaAccount::class);
    }

    /**
     * L'objet à l'origine de l'écriture (ex: Invoice, ExpenseReport, UlysConsumption).
     */
    public function sourceable(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
        ];
    }
}
