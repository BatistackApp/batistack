<?php

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
        Schema::table('rental_contracts', function (Blueprint $table) {
            // Le champ periodicity existe déjà dans le modèle via l'enum, mais vérifions s'il est en base.
            // D'après l'analyse précédente, il semble être utilisé mais pas explicitement vu dans les migrations listées.
            // Par sécurité, on l'ajoute s'il n'existe pas, ou on ajoute un champ pour la prochaine date de facturation.

            if (!Schema::hasColumn('rental_contracts', 'next_billing_date')) {
                $table->date('next_billing_date')->nullable()->after('end_date')->comment('Prochaine date de génération de facture fournisseur');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropColumn(['next_billing_date']);
        });
    }
};
