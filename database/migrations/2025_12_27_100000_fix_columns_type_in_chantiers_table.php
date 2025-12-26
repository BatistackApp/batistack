<?php

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
        Schema::table('chantiers', function (Blueprint $table) {
            // Le type 'string' avec précision/échelle est invalide et a probablement été interprété comme un simple VARCHAR.
            // On change pour le type DECIMAL approprié pour les coordonnées GPS.
            // La méthode change() nécessite le package doctrine/dbal.
            $table->decimal('latitude', 10, 8)->nullable()->change();
            $table->decimal('longitude', 11, 8)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chantiers', function (Blueprint $table) {
            // On remet le type string, même si c'était une erreur, pour la réversibilité.
            $table->string('latitude')->nullable()->change();
            $table->string('longitude')->nullable()->change();
        });
    }
};
