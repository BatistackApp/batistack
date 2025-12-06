<?php

namespace App\Models\GED;

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Observers\GED\DocumentObserver;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[ObservedBy([DocumentObserver::class])]
class Document extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, BelongsToCompany;
    protected $guarded = [];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(DocumentFolder::class);
    }

    public function chantiers(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class);
    }

    protected function casts(): array
    {
        return [
            'expiration_date' => 'date',
            'is_valid' => 'boolean',
        ];
    }

    // Le fichier physique
    // Spatie permet de définir des collections (ex: 'files', 'images')
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile(); // Un document = Un fichier physique (pour la version simple)
    }

    // Helper pour récupérer l'URL
    public function getDownloadUrlAttribute(): string
    {
        return $this->getFirstMediaUrl('file');
    }

    public function getSizeAttribute(): string
    {
        return $this->getFirstMedia('file')?->human_readable_size;
    }
}
