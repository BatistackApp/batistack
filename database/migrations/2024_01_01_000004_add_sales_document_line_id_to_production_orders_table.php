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
            $table->foreignId('sales_document_line_id')
                  ->nullable()
                  ->after('id')
                  ->comment('Lien vers la ligne de commande client')
                  ->constrained('sales_document_lines')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropForeign(['sales_document_line_id']);
            $table->dropColumn('sales_document_line_id');
        });
    }
};
