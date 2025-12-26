<?php

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\RH\Employee;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chantiers::class)->nullable()->constrained()->cascadeOnDelete();

            $table->foreignIdFor(\App\Models\Fleets\Fleet::class)->nullable()->constrained()->nullOnDelete();
            // Pour les véhicules : Kilométrage de fin de journée
            $table->integer('end_mileage')->nullable();
            // Pour les engins : Lecture du compteur d'heures
            $table->decimal('hours_read', 10, 2)->nullable();


            $table->date('date');
            $table->string('type')->default('work');
            $table->decimal('hours', 4, 2);
            $table->boolean('lunch_basket')->default(false);
            $table->boolean('travel_zone')->default(false);
            $table->boolean('is_validated')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['employee_id', 'date']);
            $table->index(['chantiers_id']); // Correction: project_id n'existe pas, c'est chantiers_id
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timesheets');
    }
};
