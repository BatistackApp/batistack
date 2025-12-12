<?php

namespace App\Models\Comptabilite;

use App\Enums\Comptabilite\JournalType;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComptaJournal extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    public function entries(): HasMany
    {
        return $this->hasMany(ComptaEntry::class, 'journal_id');
    }

    protected function casts(): array
    {
        return [
            'type' => JournalType::class,
            'is_default' => 'boolean',
        ];
    }
}
