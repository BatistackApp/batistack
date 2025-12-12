<?php

use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compta_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->string('number', 15); // Ex: 401ULYS, 628000, 701000
            $table->string('name');
            $table->string('class_code', 2)->nullable(); // Ex: 4, 6, 7 (pour les catégories)
            $table->boolean('is_auxiliary')->default(false); // Est-ce un compte fournisseur ou client (auxiliaire)
            $table->timestamps();

            $table->unique(['company_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compta_accounts');
    }
};
