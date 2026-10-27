<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Entries are anonymous: they come in through a location's public form link.
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('campaign_locations')->nullOnDelete();
            // Where the device was at submit time, kept for auditing the geofence check.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedInteger('location_accuracy_meters')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->boolean('consent_accepted')->default(false);
            $table->text('consent_text_version')->nullable();
            $table->boolean('qualified')->nullable();
            $table->timestamp('submitted_at');
            $table->string('status')->default('submitted');
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'submitted_at']);
            $table->index(['location_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
