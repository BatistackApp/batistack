<?php

use App\Models\Core\Company;
use App\Models\Fleets\Fleet;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ulys_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Fleet::class)->constrained()->cascadeOnDelete();

            // Données de transaction Ulys
            $table->string('ulys_transaction_id')->unique()->comment('ID unique de transaction Ulys (pour éviter les doublons).');
            $table->string('badge_id')->comment('ID du badge Ulys (référence Fleet.ulys_badge_id).');
            $table->timestamp('transaction_date')->comment('Date et heure du passage.');
            $table->decimal('amount', 8, 2)->comment('Montant total TTC de la transaction.');
            $table->string('currency')->default('EUR');
            $table->string('toll_station')->nullable()->comment('Nom du poste de péage.');
            $table->string('entry_station')->nullable()->comment('Poste d\'entrée (si disponible).');
            $table->string('exit_station')->nullable()->comment('Poste de sortie (si disponible).');

            // Données brutes (pour référence et cas non gérés)
            $table->json('raw_data');

            $table->timestamps();

            $table->index(['fleet_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulys_consumptions');
    }
};
