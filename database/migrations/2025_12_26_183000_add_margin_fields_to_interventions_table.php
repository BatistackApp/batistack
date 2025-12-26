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
        Schema::table('interventions', function (Blueprint $table) {
            $table->decimal('target_margin_rate', 5, 2)->nullable()->comment('Taux de marge cible (%) pour cette intervention');
            $table->decimal('actual_margin', 12, 2)->nullable()->comment('Marge réelle réalisée');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropColumn(['target_margin_rate', 'actual_margin']);
        });
    }
};
