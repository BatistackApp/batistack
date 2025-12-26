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
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Tags\HasTags;

#[ObservedBy([DocumentObserver::class])]
class Document extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, BelongsToCompany, Searchable, HasTags;
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
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')
            ->singleFile();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        $array = $this->toArray();

        // On y ajoute le contenu du fichier extrait par Spatie/Tika
        // Cette partie nécessite que le driver Tika soit configuré pour Spatie Media Library
        if ($this->hasMedia('file')) {
            $array['file_content'] = (string) $this->getFirstMedia('file')->getCustomProperty('text');
        }

        // On ajoute les tags pour qu'ils soient aussi cherchables
        $array['tags'] = $this->tags->pluck('name')->implode(' ');

        return $array;
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
