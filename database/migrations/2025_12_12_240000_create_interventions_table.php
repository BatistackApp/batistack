<?php

use App\Enums\Interventions\InterventionStatus;
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
        Schema::create('interventions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('chantier_id')->nullable()->constrained('chantiers')->nullOnDelete();
            $table->foreignId('client_id')->constrained('tiers')->cascadeOnDelete(); // Le client est un Tiers
            $table->foreignId('technician_id')->nullable()->constrained('employees')->nullOnDelete(); // Le technicien est un Employé
            $table->string('status')->default(InterventionStatus::Planned->value);
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('planned_start_date');
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->text('report')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interventions');
    }
};
