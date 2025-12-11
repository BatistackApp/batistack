<?php

use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('fleets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('internal_ref')->unique();
            $table->string('type')->default('vehicle');

            $table->string('registration')->nullable()->unique();
            $table->string('serial_number')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();

            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('current_value', 15, 2)->default(0);
            $table->boolean('is_available')->default(true);

            $table->integer('kilometrage')->default(0);
            $table->date('last_check_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fleets');
    }
};
