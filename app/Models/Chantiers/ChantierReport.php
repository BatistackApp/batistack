<?php

namespace App\Models\Chantiers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChantierReport extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'chantier_id',
        'type',
        'path',
    ];

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }
}
