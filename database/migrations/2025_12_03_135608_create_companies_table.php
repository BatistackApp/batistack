<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('tax_number')->nullable();

            // Gestion de l'abonnement principal (Formule 1, 2 ou 3)
            // On stocke l'identifiant du plan (ex: 'plan_starter', 'plan_pro')
            $table->string('current_plan');
            $table->string('stripe_id')->nullable()->index();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('companies');
    }
};
