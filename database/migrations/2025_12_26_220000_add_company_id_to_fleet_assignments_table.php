<?php

use App\Models\Core\Company;
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
        Schema::table('fleet_assignments', function (Blueprint $table) {
            if (!Schema::hasColumn('fleet_assignments', 'company_id')) {
                $table->foreignIdFor(Company::class)->after('id')->constrained()->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleet_assignments', function (Blueprint $table) {
            // dropForeign takes an array of columns
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
        });
    }
};
