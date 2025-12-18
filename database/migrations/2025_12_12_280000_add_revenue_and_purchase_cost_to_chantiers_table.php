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
            $table->decimal('total_sales_revenue', 15, 2)->default(0)->after('total_rental_cost');
            $table->decimal('total_purchase_cost', 15, 2)->default(0)->after('total_sales_revenue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chantiers', function (Blueprint $table) {
            $table->dropColumn(['total_sales_revenue', 'total_purchase_cost']);
        });
    }
};
