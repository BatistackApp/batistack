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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Employee::class)->nullable()->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chantiers::class)->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->string('category')->default('meal');
            $table->string('label');
            $table->decimal('amount_ht', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->decimal('amount_ttc', 10, 2)->default(0);

            // Justificatif (Chemin vers le fichier stocké)
            // Note: Filament gère ça via Spatie Media Library souvent, mais un champ simple suffit pour démarrer
            $table->string('proof_path')->nullable();

            $table->string('status')->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_billable')->default(false);
            $table->boolean('has_been_billed')->default(false);
            $table->timestamps();

            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
