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
            $table->boolean('is_billable')->default(true)->after('status');
            $table->foreignId('sales_document_id')->nullable()->after('costs_posted_to_compta')->constrained('sales_documents')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interventions', function (Blueprint $table) {
            $table->dropForeign(['sales_document_id']);
            $table->dropColumn(['is_billable', 'sales_document_id']);
        });
    }
};
