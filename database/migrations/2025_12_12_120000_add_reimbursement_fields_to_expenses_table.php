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
        Schema::table('expenses', function (Blueprint $table) {
            $table->timestamp('reimbursed_at')->nullable()->after('has_been_billed');
            $table->foreignId('reimbursed_by_payroll_slip_id')->nullable()->after('reimbursed_at')->constrained('payroll_slips')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['reimbursed_by_payroll_slip_id']);
            $table->dropColumn(['reimbursed_at', 'reimbursed_by_payroll_slip_id']);
        });
    }
};
