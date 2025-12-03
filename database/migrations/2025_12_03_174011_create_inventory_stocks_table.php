<?php

use App\Models\Articles\Product;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();

            // Stock physique réel
            $table->decimal('quantity_on_hand', 12, 4)->default(0);

            // Stock réservé (sur des chantiers en cours non livrés)
            $table->decimal('quantity_reserved', 12, 4)->default(0);

            // Seuil d'alerte (Optionnel, pour réapprovisionnement)
            $table->decimal('quantity_alert', 12, 4)->nullable();

            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
