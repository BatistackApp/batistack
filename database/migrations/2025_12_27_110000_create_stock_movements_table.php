<?php

use App\Models\Articles\Product;
use App\Models\Articles\Warehouse;
use App\Models\Core\Company;
use App\Models\User;
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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Warehouse::class)->constrained()->cascadeOnDelete();

            $table->decimal('quantity_change', 12, 4); // Négatif pour une sortie, positif pour une entrée
            $table->decimal('quantity_after', 12, 4); // Stock après le mouvement

            $table->string('reason')->nullable(); // Ex: "Vente", "Achat", "Inventaire", "Production"
            $table->nullableMorphs('sourceable'); // Lien vers l'objet source (Facture, OF, etc.)

            $table->foreignIdFor(User::class, 'user_id')->nullable()->comment('Utilisateur à l\'origine du mouvement');
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
