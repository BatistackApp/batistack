<?php

use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->boolean('is_customer')->default(false)->index();
            $table->boolean('is_supplier')->default(false)->index();
            $table->boolean('is_subcontractor')->default(false)->index();
            $table->string('nature')->default('company');
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('siret_number')->nullable();
            $table->string('naf_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('ville')->nullable()->default('FR');
            $table->string('pays');
            $table->string('payment_condition')->nullable();
            $table->decimal('outstanding_limit', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tiers');
    }
};
