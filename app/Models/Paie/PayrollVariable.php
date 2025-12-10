<?php

namespace App\Models\Paie;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollVariable extends Model
{
    use HasFactory;

    public function payrollSlip()
    {
        return $this->belongsTo(PayrollSlip::class);
    }
}
