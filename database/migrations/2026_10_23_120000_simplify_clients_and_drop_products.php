<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR 005: client companies and products are removed. A client is just a read-only account that the
 * EO grants access to specific campaigns. Existing access is kept by granting each client account
 * every campaign its former company owned.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Guarded so a half-applied run can be retried safely.
        if (! Schema::hasTable('campaign_user')) {
            Schema::create('campaign_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['campaign_id', 'user_id']);
            });
        }

        $now = now();
        DB::table('users')
            ->join('campaigns', 'campaigns.client_id', '=', 'users.client_id')
            ->where('users.role', 'client')
            ->whereNotNull('users.client_id')
            ->get(['campaigns.id as campaign_id', 'users.id as user_id'])
            ->each(fn ($row) => DB::table('campaign_user')->insertOrIgnore([
                'campaign_id' => $row->campaign_id,
                'user_id' => $row->user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]));

        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'status']);
            $table->dropConstrainedForeignId('client_id');
            $table->index('status');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_id');
        });

        Schema::dropIfExists('campaign_products');
        Schema::dropIfExists('products');
        Schema::dropIfExists('clients');
    }

    public function down(): void
    {
        throw new RuntimeException('Client companies and products cannot be restored by rolling back. Restore a database backup instead.');
    }
};
