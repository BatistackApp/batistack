<?php

use App\Models\Fleets\Fleet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Fleet::class)->constrained()->cascadeOnDelete();

            $table->string('insurer_name')->nullable();
            $table->string('contract_number');
            $table->date('start_date');
            $table->date('end_date');

            $table->decimal('annual_cost', 10, 2)->default(0);
            $table->text('coverage_details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurances');
    }
};
