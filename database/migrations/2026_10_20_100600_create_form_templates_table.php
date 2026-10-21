<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->string('status')->default('draft');
            $table->boolean('requires_consent')->default(false);
            $table->text('consent_text')->nullable();
            $table->boolean('qualification_enabled')->default(false);
            $table->json('qualification_rule')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'name', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_templates');
    }
};
