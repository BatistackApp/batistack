<?php

namespace App\Models\RH;

use App\Enums\RH\TimesheetType;
use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
use App\Observers\RH\TimesheetObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([TimesheetObserver::class])]
class Timesheet extends Model
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

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }


    protected function casts(): array
    {
        return [
            'date' => 'date',
            'type' => TimesheetType::class,
            'hours' => 'decimal:2',
            'lunch_basket' => 'boolean',
            'is_validated' => 'boolean',
            'end_mileage' => 'integer',
            'hours_read' => 'decimal:2',
        ];
    }

    /**
     * Calcule le coût théorique de cette session de travail
     * (Heures * Taux chargé de l'employé)
     */
    public function getCostAttribute(): float
    {
        if ($this->type !== TimesheetType::Work && $this->type !== TimesheetType::Travel) {
            return 0; // On ne compte pas les congés dans le coût chantier ici
        }

        // Attention N+1 : Il faudra eager loader 'employee'
        return $this->hours * ($this->employee->hourly_cost ?? 0);
    }
}
