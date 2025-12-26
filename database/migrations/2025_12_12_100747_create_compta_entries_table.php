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
        Schema::create('compta_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Company::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ComptaJournal::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ComptaAccount::class)->constrained()->cascadeOnDelete();

            $table->date('date')->comment('Date de l\'écriture.');
            $table->string('reference')->nullable()->comment('Référence de la pièce (ex: Facture N°).');
            $table->string('label')->comment('Libellé de l\'écriture.');

            $table->decimal('debit', 12, 2)->default(0);
            $table->decimal('credit', 12, 2)->default(0);

            // Polymorphisme pour lier à l'origine (UlysConsumption, Invoice, ExpenseReport...)
            $table->morphs('sourceable');

            $table->timestamps();

            $table->index(['company_id', 'date']);
            $table->index(['compta_journal_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compta_entries');
    }
};
