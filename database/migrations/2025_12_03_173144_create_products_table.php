<?php

use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->string('type')->default('material')->index();
            $table->string('unit')->default('u');

            $table->string('reference');
            $table->string('name');
            $table->text('description')->nullable();

            $table->string('supplier_reference')->nullable();
            $table->string('ean_code')->nullable()->index();
            $table->foreignIdFor(Tiers::class, 'main_supplier_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('buying_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(20.00);

            $table->boolean('is_stockable')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
