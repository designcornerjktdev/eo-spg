<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_submission_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->string('field_type');
            $table->string('value_text')->nullable();
            $table->decimal('value_number', 12, 2)->nullable();
            $table->timestamps();

            $table->index(['field_key', 'field_type']);
            $table->index('form_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_field_values');
    }
};
