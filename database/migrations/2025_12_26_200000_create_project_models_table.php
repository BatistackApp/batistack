<?php

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_models', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chantiers::class)->constrained()->cascadeOnDelete();

            $table->string('name')->comment('Nom de la maquette (ex: Architecture, CVC)');
            $table->string('format', 10)->default('ifc')->comment('Format du fichier (ifc, glb, rvt)');
            $table->boolean('is_georeferenced')->default(false)->comment('Si true, utilise les coordonnées internes du fichier');

            // Champs de calage manuel si le fichier n'est pas géoréférencé correctement
            $table->decimal('model_origin_latitude', 10, 8)->nullable()->comment('Latitude du point 0,0,0 du modèle');
            $table->decimal('model_origin_longitude', 11, 8)->nullable()->comment('Longitude du point 0,0,0 du modèle');
            $table->decimal('altitude_offset', 8, 2)->default(0)->comment('Décalage en hauteur (m)');
            $table->decimal('rotation_z', 5, 2)->default(0)->comment('Rotation par rapport au Nord (degrés)');
            $table->decimal('scale', 5, 3)->default(1.000)->comment('Facteur d\'échelle');

            $table->json('metadata')->nullable()->comment('Métadonnées extraites (Auteur, Logiciel, Schema IFC)');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_models');
    }
};
