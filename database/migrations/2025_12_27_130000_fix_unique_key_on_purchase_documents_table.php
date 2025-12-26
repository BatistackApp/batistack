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
        Schema::table('purchase_documents', function (Blueprint $table) {
            // 1. Supprimer l'ancienne contrainte unique si elle existe
            // Laravel génère un nom d'index comme 'tablename_columnname_unique'
            $table->dropUnique('purchase_documents_reference_unique');

            // 2. Ajouter la nouvelle contrainte unique composite
            $table->unique(['company_id', 'tiers_id', 'reference'], 'purchase_documents_company_tier_ref_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_documents', function (Blueprint $table) {
            // 1. Supprimer la nouvelle contrainte
            $table->dropUnique('purchase_documents_company_tier_ref_unique');

            // 2. Rétablir l'ancienne contrainte
            $table->unique('reference');
        });
    }
};
