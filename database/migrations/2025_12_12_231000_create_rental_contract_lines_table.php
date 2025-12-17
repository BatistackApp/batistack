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
        Schema::create('rental_contract_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_contract_id')->constrained('rental_contracts')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('vat_rate', 5, 2)->default(20.00);
            $table->decimal('total_ht', 15, 2);
            $table->decimal('total_vat', 15, 2);
            $table->decimal('total_ttc', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_contract_lines');
    }
};
