<?php

use App\Models\Articles\Product;
use App\Models\GPAO\ProductionOrder;
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
        // 1. Définition des points de contrôle (Modèle)
        Schema::create('quality_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->constrained()->cascadeOnDelete();

            $table->string('label'); // Ex: "Vérification des soudures", "Test d'étanchéité"
            $table->text('description')->nullable();
            $table->boolean('is_mandatory')->default(true); // Bloquant ou non

            $table->timestamps();
        });

        // 2. Résultats des contrôles (Instance sur un OF)
        Schema::create('quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProductionOrder::class)->constrained()->cascadeOnDelete();
            $table->foreignId('quality_checkpoint_id')->constrained('quality_checkpoints')->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'checked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->boolean('is_passed')->default(false); // Validé ou non
            $table->text('notes')->nullable(); // Commentaire du contrôleur
            $table->timestamp('checked_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_controls');
        Schema::dropIfExists('quality_checkpoints');
    }
};
