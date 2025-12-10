<?php

namespace App\Models\Paie;

use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollPeriods extends Model
{
    use HasFactory;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts()
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }
}
