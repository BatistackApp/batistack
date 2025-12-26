<?php

namespace App\Models\Chantiers;

use App\Models\Core\Company;
use App\Trait\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ProjectModel extends Model implements HasMedia
{
    use SoftDeletes, BelongsToCompany, InteractsWithMedia;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_georeferenced' => 'boolean',
            'model_origin_latitude' => 'decimal:8',
            'model_origin_longitude' => 'decimal:8',
            'altitude_offset' => 'decimal:2',
            'rotation_z' => 'decimal:2',
            'scale' => 'decimal:3',
            'metadata' => 'array',
        ];
    }

    public function chantier(): BelongsTo
    {
        return $this->belongsTo(Chantiers::class, 'chantiers_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('model_file')
            ->singleFile()
            ->acceptsMimeTypes([
                'application/x-step', // IFC souvent détecté comme ça
                'model/gltf-binary',
                'model/gltf+json',
                'application/octet-stream', // Fallback générique
                'text/plain' // Parfois IFC est vu comme texte
            ]);
    }
}
