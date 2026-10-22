<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Superadmin login dengan email; client & SPG login dengan username (email opsional).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('email')->nullable()->change();
        });

        // Isi username akun lama dari bagian depan email, dibuat unik bila bentrok.
        DB::table('users')->where('role', '!=', 'superadmin')->orderBy('id')->get(['id', 'email'])->each(function ($user) {
            $base = Str::of((string) $user->email)->before('@')->lower()->replaceMatches('/[^a-z0-9._-]/', '')->value() ?: 'user';
            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.(++$suffix);
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
