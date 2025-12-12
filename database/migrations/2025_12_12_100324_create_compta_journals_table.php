<?php

use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compta_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();

            $table->string('code', 5)->comment('Code du journal (ex: ACH, VTE, BQ1).');
            $table->string('label')->comment('Libellé (ex: Journal des Achats).');
            $table->string('type', 10)->comment('Type de journal (Achat, Vente, Banque, OD).');

            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compta_journals');
    }
};
