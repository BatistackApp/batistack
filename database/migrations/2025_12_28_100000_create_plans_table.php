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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ex: "Essentiel", "Pro", "Entreprise"
            $table->string('slug')->unique(); // Ex: "essentiel", "pro"
            $table->text('description')->nullable();

            // Prix (HT)
            $table->decimal('price_monthly', 10, 2)->default(0);
            $table->decimal('price_yearly', 10, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true); // Visible sur le site public ?
            $table->integer('sort_order')->default(0); // Pour l'affichage

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
