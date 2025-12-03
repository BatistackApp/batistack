<?php

use App\Models\Core\Company;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('company_features', function (Blueprint $table) {
            $table->id();

            // Le code du module (ex: 'module_chantier_3d', 'opt_sms_pack')
            $table->string('feature_code')->index();

            // Valeur optionnelle (ex: '500' pour un quota de 500 SMS)
            $table->string('value')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignIdFor(Company::class);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_features');
    }
};
