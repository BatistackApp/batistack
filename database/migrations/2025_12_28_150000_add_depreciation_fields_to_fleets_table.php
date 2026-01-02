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
        Schema::table('fleets', function (Blueprint $table) {
            $table->decimal('residual_value', 15, 2)->default(0)->after('purchase_price');
            $table->integer('depreciation_duration_years')->default(5)->after('residual_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn(['residual_value', 'depreciation_duration_years']);
        });
    }
};
