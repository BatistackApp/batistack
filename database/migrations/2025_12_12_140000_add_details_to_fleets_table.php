<?php

use App\Enums\Fleets\FleetType;
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
        Schema::table('fleets', function (Blueprint $table) {
            $table->string('registration_number')->nullable()->unique()->after('name');
            $table->string('vin')->nullable()->unique()->after('model');
            $table->unsignedInteger('mileage')->default(0)->after('vin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropColumn(['name', 'registration_number', 'type', 'brand', 'model', 'vin', 'mileage']);
        });
    }
};
