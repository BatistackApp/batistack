<?php

namespace App\Models\Fleets;

use App\Models\Core\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fleet extends Model
{
    use HasFactory;

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function casts()
    {
        return [
            'purchase_date' => 'date',
            'is_available' => 'boolean',
            'last_check_date' => 'date',
        ];
    }
}
