<?php

namespace App\Models\Comptabilite;

use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComptaRecurringEntryTemplate extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(ComptaJournal::class, 'journal_id');
    }

    public function debit(): BelongsTo
    {
        return $this->belongsTo(ComptaAccount::class, 'account_debit_id');
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(ComptaAccount::class, 'account_credit_id');
    }

    protected function casts(): array
    {
        return [
            'last_posting_date' => 'date',
            'next_posting_date' => 'date',
            'is_active' => 'boolean',
            'amount' => 'decimal',
        ];
    }
}
