<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_locations', function (Blueprint $table) {
            // Location point picked on the campaign form map; optional so older locations stay valid.
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
