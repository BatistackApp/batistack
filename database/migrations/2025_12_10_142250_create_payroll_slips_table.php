<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('payroll_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('total_hours')->default(0);
            $table->decimal('total_expenses_amount', 10, 2)->default(0);

            // Commentaire pour le gestionnaire de paie (ex: "Prime exceptionnelle validée par le patron")
            $table->text('manager_comment')->nullable();
            $table->boolean('is_validated')->default(false); // Validé en interne avant envoi
            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_slips');
    }
};
