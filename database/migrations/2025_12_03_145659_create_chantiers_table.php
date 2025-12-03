<?php

use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('chantiers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Tiers::class)->constrained()->cascadeOnDelete();
            $table->string('reference');
            $table->string('name');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_overdue')->default(false)->index();
            $table->string('address')->nullable();
            $table->string('code_postal')->nullable();
            $table->string('ville')->nullable();
            $table->string('pays')->default('FR');
            $table->string('latitude', 10, 8)->nullable();
            $table->string('longitude', 10, 8)->nullable();
            $table->date('date_start')->nullable();
            $table->date('end_date_planned')->nullable();
            $table->date('end_date_real')->nullable();
            $table->longText('description')->nullable();
            $table->text('access_instructions')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'reference']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('chantiers');
    }
};
