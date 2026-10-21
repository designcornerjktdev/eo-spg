<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            // Baris pengganti menunjuk ke baris lama yang di-void karena koreksi SPG.
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
