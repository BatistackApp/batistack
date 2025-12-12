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
        Schema::table('production_orders', function (Blueprint $table) {
            $table->nullableMorphs('assigned_to'); // assigned_to_id and assigned_to_type
            $table->date('planned_start_date')->nullable()->after('quantity');
            $table->date('planned_end_date')->nullable()->after('planned_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropMorphs('assigned_to');
            $table->dropColumn('planned_start_date');
            $table->dropColumn('planned_end_date');
        });
    }
};
