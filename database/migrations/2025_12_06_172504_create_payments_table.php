<?php

use App\Models\Banque\BankAccount;
use App\Models\Banque\BankTransaction;
use App\Models\Core\Company;
use App\Models\Tiers\Tiers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Tiers::class)->constrained();
            $table->foreignIdFor(BankAccount::class)->nullable()->constrained();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('reference')->nullable();
            $table->date('date');
            $table->nullableMorphs('payable');
            $table->foreignIdFor(BankTransaction::class)->nullable()->constrained()->nullOnDelete();
            $table->text('note')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
