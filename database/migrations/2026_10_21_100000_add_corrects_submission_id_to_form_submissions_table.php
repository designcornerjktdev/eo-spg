<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            // The replacement row points to the old row that was voided by an SPG correction.
            $table->foreignId('corrects_submission_id')->nullable()->after('void_reason')->constrained('form_submissions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrects_submission_id');
        });
    }
};
