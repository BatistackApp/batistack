<?php

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\GED\DocumentFolder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DocumentFolder::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Chantiers::class)->nullable()->constrained()->cascadeOnDelete();
            $table->nullableMorphs('documentable');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('expiration_date')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
