<?php

use App\Enums\Fleets\MaintenanceType;
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
        Schema::table('maintenances', function (Blueprint $table) {
            $table->string('type')->default(MaintenanceType::Scheduled->value)->after('fleet_id');
            $table->text('description')->nullable()->after('type');
            $table->string('provider_name')->nullable()->after('cost');
            $table->unsignedInteger('mileage_at_maintenance')->nullable()->after('date_maintenance');
            $table->unsignedInteger('next_mileage')->nullable()->after('next_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropColumn(['type', 'description', 'provider_name', 'mileage_at_maintenance', 'next_mileage']);
        });
    }
};
