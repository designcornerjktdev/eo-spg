<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            // One random token per filled-in form, so a retried or double-tapped submit is saved once.
            $table->string('client_token', 64)->nullable()->unique()->after('form_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropUnique(['client_token']);
            $table->dropColumn('client_token');
        });
    }
};
