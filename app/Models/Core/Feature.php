<?php

namespace App\Models\Core;

use App\Enums\Core\TypeFeature;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Feature extends Model
{

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_optional' => 'boolean',
            'type' => TypeFeature::class,
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_feature')->withPivot('value');
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                return 'module-'.Str::slug($this->name);
            }
        );
    }

    /**
     * Get the parsed HTML documentation for the feature.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function documentationHtml(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                $slug = 'module-'.Str::slug($this->name);

                if (!$slug) {
                    return '<p>Documentation non disponible (code de fonctionnalité manquant).</p>';
                }

                // Construit le nom du fichier en se basant sur le code de la feature.
                // Exemple: 'module_chantiers' -> 'module-chantiers-version.md'
                $fileName = $slug . '-version.md';
                $filePath = base_path('docs/modules/' . $fileName);

                if (File::exists($filePath)) {
                    $markdownContent = File::get($filePath);
                    return Str::markdown($markdownContent);
                }

                return '<p>Aucune documentation disponible pour ce module.</p>';
            }
        );
    }
}
