<?php

use App\Models\Articles\Product;
use App\Models\Facturation\SalesDocument;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_document_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(SalesDocument::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('product');
            $table->integer('sort_order')->default(0);

            $table->string('label');
            $table->text('description')->nullable();

            $table->decimal('quantity', 12, 4)->default(0);
            $table->string('unit')->default('u');

            $table->decimal('unit_price', 12, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(20.00);
            $table->decimal('discount_rate', 5, 2)->default(0);

            // Données cachées pour le calcul de marge (Commerce)
            $table->decimal('buying_price', 12, 2)->default(0); // PA / Déboursé unitaire
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_document_lines');
    }
};
