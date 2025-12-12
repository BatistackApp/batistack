<?php

namespace App\Models\Comptabilite;

use App\Enums\Comptabilite\AccountClass;
use App\Models\Core\Company;
use App\Observers\Comptabilite\ComptaAccountObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([ComptaAccountObserver::class])]
class ComptaAccount extends Model
{
    use HasFactory, BelongsToCompany;
    protected $guarded = [];

    public function entries(): HasMany
    {
        return $this->hasMany(ComptaEntry::class);
    }

    protected function casts(): array
    {
        return [
            'class_code' => AccountClass::class,
            'is_auxiliary' => 'boolean',
            'is_lettrable' => 'boolean',
        ];
    }
}
