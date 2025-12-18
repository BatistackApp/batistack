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
            $table->decimal('budgeted_revenue', 15, 2)->default(0)->after('total_purchase_cost');
            $table->decimal('budgeted_labor_cost', 15, 2)->default(0)->after('budgeted_revenue');
            $table->decimal('budgeted_material_cost', 15, 2)->default(0)->after('budgeted_labor_cost');
            $table->decimal('budgeted_rental_cost', 15, 2)->default(0)->after('budgeted_material_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chantiers', function (Blueprint $table) {
            $table->dropColumn(['budgeted_revenue', 'budgeted_labor_cost', 'budgeted_material_cost', 'budgeted_rental_cost']);
        });
    }
};
