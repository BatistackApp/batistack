<?php

namespace App\Models\GPAO;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityControl extends Model
{
   protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_passed' => 'boolean',
            'checked_at' => 'datetime',
        ];
    }

    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    public function checkpoint(): BelongsTo
    {
        return $this->belongsTo(QualityCheckpoint::class, 'quality_checkpoint_id');
    }

    public function controller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }
}
