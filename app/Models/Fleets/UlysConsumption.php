<?php

namespace App\Models\Fleets;

use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UlysConsumption extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    protected function casts(): array
    {
        return [
            'transaction_date' => 'datetime',
            'amount' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

}
