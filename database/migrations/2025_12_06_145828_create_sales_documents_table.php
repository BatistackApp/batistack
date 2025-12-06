<?php

use App\Models\Chantiers\Chantiers;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Tiers::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chantiers::class)->nullable()->constrained()->nullOnDelete();

            $table->foreignId('parent_document_id')->nullable()->constrained('sales_documents')->nullOnDelete();

            $table->string('type')->index();
            $table->string('reference')->index();
            $table->integer('version')->default(1);
            $table->string('status')->default('draft')->index();

            $table->date('date'); // Date d'émission
            $table->date('validity_date')->nullable(); // Fin de validité (Devis)
            $table->date('due_date')->nullable(); // Échéance de paiement (Facture)

            $table->string('currency_code')->default('EUR');
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->decimal('total_vat', 12, 2)->default(0);
            $table->decimal('total_ttc', 12, 2)->default(0);

            // Spécifique BTP / Commerce
            // Marge théorique calculée au moment du devis (pour stats)
            $table->decimal('total_cost')->nullable(); // Total Déboursé
            $table->decimal('margin_amount')->nullable(); // Marge

            $table->text('header_note')->nullable();
            $table->text('footer_note')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'type', 'reference', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_documents');
    }
};
