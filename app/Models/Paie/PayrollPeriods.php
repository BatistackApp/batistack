<?php

namespace App\Models\Paie;

use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriods extends Model
{
    use HasFactory, BelongsToCompany;

    protected $guarded = [];


    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
