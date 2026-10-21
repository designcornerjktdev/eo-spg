<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('type');
            $table->boolean('required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('visible_to_client')->default(true);
            $table->boolean('is_identity')->default(false);
            $table->boolean('show_on_dashboard')->default(false);
            $table->json('options')->nullable();
            $table->decimal('min_value', 12, 2)->nullable();
            $table->decimal('max_value', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['form_template_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
