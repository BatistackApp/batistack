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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('payroll_external_reference_id')->nullable()->comment('Identifiant dossier Silae/Sage');
            $table->string('payroll_export_format')->default('generic_csv')->comment('Format export paie (silae, sage, generic_csv)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['payroll_external_reference_id', 'payroll_export_format']);
        });
    }
};
