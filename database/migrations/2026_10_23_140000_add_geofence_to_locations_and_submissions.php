<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entries are only accepted within this distance of the location's map point.
        Schema::table('campaign_locations', function (Blueprint $table) {
            $table->unsignedInteger('radius_meters')->default(300)->after('longitude');
        });

        // Where the device was when the entry was submitted, kept for auditing the geofence check.
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('location_id');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->unsignedInteger('location_accuracy_meters')->nullable()->after('longitude');
            $table->unsignedInteger('distance_meters')->nullable()->after('location_accuracy_meters');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'location_accuracy_meters', 'distance_meters']);
        });

        Schema::table('campaign_locations', function (Blueprint $table) {
            $table->dropColumn('radius_meters');
        });
    }
};
