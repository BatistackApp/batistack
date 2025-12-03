<?php

use App\Models\Articles\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class, 'parent_product_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class, 'child_product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->timestamps();

            // Un produit ne peut pas se contenir lui-même directement (éviter boucles simples)
            // La protection contre les boucles infinies profondes se fera en PHP
            $table->unique(['parent_product_id', 'child_product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_assemblies');
    }
};
