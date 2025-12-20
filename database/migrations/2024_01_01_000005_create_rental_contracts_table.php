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
        Schema::create('rental_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();

            $table->foreignId('tiers_id')->comment('Locataire')->constrained('tiers')->cascadeOnDelete();
            $table->foreignId('fleet_id')->comment('Matériel loué')->constrained('fleets')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');
            $table->string('status');

            $table->decimal('daily_rate', 15, 2)->comment('Tarif journalier');
            $table->decimal('total_amount', 15, 2)->comment('Montant total calculé');

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_contracts');
    }
};
