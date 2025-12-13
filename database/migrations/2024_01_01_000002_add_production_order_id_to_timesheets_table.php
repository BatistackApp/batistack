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
        Schema::table('timesheets', function (Blueprint $table) {
            $table->foreignId('production_order_id')
                  ->nullable()
                  ->after('chantiers_id')
                  ->comment('Lien vers un ordre de fabrication')
                  ->constrained('production_orders')
                  ->nullOnDelete(); // Si l'OF est supprimé, on ne supprime pas le pointage, on met juste l'ID à null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropForeign(['production_order_id']);
            $table->dropColumn('production_order_id');
        });
    }
};
