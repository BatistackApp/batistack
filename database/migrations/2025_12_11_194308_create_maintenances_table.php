<?php

use App\Models\Fleets\Fleet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Fleet::class)->constrained()->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->string('type');
            $table->text('description');

            $table->decimal('cost', 10 ,2)->default(0);
            $table->string('invoice_ref')->nullable();

            $table->string('next_mileage')->nullable();
            $table->date('next_date')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
