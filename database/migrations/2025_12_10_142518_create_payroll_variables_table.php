<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('payroll_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_slip_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // Enum PayrollVariableType
            $table->string('code'); // Code export (ex: HN, HS25, PANIER)
            $table->string('label'); // Libellé (ex: "Heures Normales")
            $table->decimal('quantity'); // Nombre d'heures ou d'unités
            $table->string('unit')->default('h'); // h, u, €
            // Optionnel : Valeur unitaire si connue (ex: montant du panier repas)
            $table->decimal('unit_value', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payroll_variables');
    }
};
