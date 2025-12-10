<?php

namespace App\Models\Paie;

use App\Enums\Paie\PayrollVariableType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollVariable extends Model
{
    use HasFactory;

    public function slip(): BelongsTo
    {
        return $this->belongsTo(PayrollSlip::class);
    }

    protected function casts(): array
    {
        return [
            'type' => PayrollVariableType::class,
        ];
    }
}
