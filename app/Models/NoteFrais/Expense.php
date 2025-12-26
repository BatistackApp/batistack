<?php

namespace App\Models\NoteFrais;

use App\Enums\NoteFrais\ExpenseCategory;
use App\Enums\NoteFrais\ExpenseStatus;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Paie\PayrollSlip;
use App\Models\RH\Employee;
use App\Observers\NoteFrais\ExpenseObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([ExpenseObserver::class])]
class Expense extends Model implements HasMedia
{
    use HasFactory, BelongsToCompany, InteractsWithMedia;

    protected $guarded = [];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    public function reimbursedByPayrollSlip(): BelongsTo
    {
        return $this->belongsTo(PayrollSlip::class, 'reimbursed_by_payroll_slip_id');
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
            'reimbursed_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('proof')
            ->singleFile();
    }

    // Scopes utiles pour Filament
    public function scopePending($query)
    {
        return $query->where('status', ExpenseStatus::Submitted);
    }
}
