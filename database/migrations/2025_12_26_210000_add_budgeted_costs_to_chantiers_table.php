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
        Schema::table('chantiers', function (Blueprint $table) {
            $table->decimal('budgeted_purchase_cost', 12, 2)->default(0)->comment('Budget prévisionnel des achats');
            $table->decimal('budgeted_fleet_cost', 12, 2)->default(0)->comment('Budget prévisionnel de la flotte');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chantiers', function (Blueprint $table) {
            $table->dropColumn(['budgeted_purchase_cost', 'budgeted_fleet_cost']);
        });
    }
};
