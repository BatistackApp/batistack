<?php

namespace App\Models\NoteFrais;

use App\Enums\NoteFrais\ExpenseCategory;
use App\Enums\NoteFrais\ExpenseStatus;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use App\Observers\NoteFrais\ExpenseObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ExpenseObserver::class])]
class Expense extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'category' => ExpenseCategory::class,
            'status' => ExpenseStatus::class,
            'amount_ht' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'amount_ttc' => 'decimal:2',
            'is_billable' => 'boolean',
            'has_been_billed' => 'boolean',
        ];
    }

    // Scopes utiles pour Filament
    public function scopePending($query)
    {
        return $query->where('status', ExpenseStatus::Submitted);
    }
}
