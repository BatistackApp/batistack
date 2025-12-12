<?php

use App\Models\Comptabilite\ComptaAccount;
use App\Models\Comptabilite\ComptaJournal;
use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('compta_recurring_entry_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ComptaJournal::class, 'journal_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ComptaAccount::class, 'account_debit_id')->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ComptaAccount::class, 'account_credit_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('label');
            $table->decimal('amount', 12, 2);
            $table->string('periodicity')->default('monthly'); // monthly, quarterly, yearly

            $table->date('last_posting_date')->nullable();
            $table->date('next_posting_date'); // Date où le Job doit générer l'écriture

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compta_recurring_entry_templates');
    }
};
