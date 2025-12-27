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
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Ex: "module_gpao", "max_users_10"
            $table->string('name'); // Ex: "Module GPAO", "Limite à 10 utilisateurs"
            $table->string('type')->default('module'); // 'module', 'limit', 'feature'
            $table->text('description')->nullable();
            $table->boolean('is_optional')->default(false); // Est-ce une option payante en plus d'un plan ?
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('features');
    }
};
