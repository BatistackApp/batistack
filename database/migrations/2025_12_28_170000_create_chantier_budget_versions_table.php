<?php

use App\Models\Chantiers\Chantiers;
use App\Models\User;
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
        Schema::create('chantier_budget_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Chantiers::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('version_name'); // Ex: "Budget Initial", "Avenant n°1"
            $table->text('notes')->nullable();

            // Copie des valeurs budgétaires à l'instant T
            $table->decimal('budgeted_revenue', 15, 2)->default(0);
            $table->decimal('budgeted_labor_cost', 15, 2)->default(0);
            $table->decimal('budgeted_material_cost', 15, 2)->default(0);
            $table->decimal('budgeted_rental_cost', 15, 2)->default(0);
            $table->decimal('budgeted_purchase_cost', 15, 2)->default(0);
            $table->decimal('budgeted_fleet_cost', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chantier_budget_versions');
    }
};
