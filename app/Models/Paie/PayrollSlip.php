<?php

namespace App\Models\Paie;

use App\Models\RH\Employee;
use App\Observers\Paie\PayrollSlipObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([PayrollSlipObserver::class])]
class PayrollSlip extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $guarded = [];

    public function period(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriods::class, 'payroll_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function variables(): HasMany
    {
        return $this->hasMany(PayrollVariable::class);
    }

    protected function casts(): array
    {
        return [
            'is_validated' => 'boolean',
            'processed_at' => 'datetime',
        ];
    }
}
