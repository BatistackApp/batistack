<?php

use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
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
        Schema::create('fleet_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Fleet::class)->constrained()->cascadeOnDelete();

            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('description');

            // Lien vers la source du coût (Maintenance, Insurance, UlysConsumption, etc.)
            $table->morphs('sourceable');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fleet_costs');
    }
};
