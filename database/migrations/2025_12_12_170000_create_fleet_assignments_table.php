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
        Schema::create('fleet_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fleet_id')->constrained('fleets')->cascadeOnDelete();
            $table->morphs('assignable'); // Creates assignable_id and assignable_type
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_assignments');
    }
};
