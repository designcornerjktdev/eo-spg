<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Business flow change (ADR 004): consumers fill campaign forms through a public link per location.
 * SPG accounts, assignments and SPG self-corrections are removed; existing entries are kept and
 * re-linked to their location.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Every location gets an unguessable public link token.
        Schema::table('campaign_locations', function (Blueprint $table) {
            $table->string('public_token', 32)->nullable()->unique()->after('longitude');
        });
        DB::table('campaign_locations')->whereNull('public_token')->orderBy('id')->pluck('id')->each(
            fn ($id) => DB::table('campaign_locations')->where('id', $id)->update(['public_token' => Str::random(32)]),
        );

        // 2. Entries point at their location directly instead of through an assignment.
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->foreignId('location_id')->nullable()->after('campaign_id')->constrained('campaign_locations')->nullOnDelete();
        });
        DB::table('form_submissions')->whereNotNull('assignment_id')->update([
            'location_id' => DB::raw('(select location_id from assignments where assignments.id = form_submissions.assignment_id)'),
        ]);

        // 3. Entries are anonymous now; old SPG authorship is dropped together with SPG accounts.
        Schema::table('form_submissions', function (Blueprint $table) {
            $table->dropIndex(['assignment_id', 'status']);
            $table->dropIndex(['submitted_by', 'submitted_at']);
            $table->dropConstrainedForeignId('assignment_id');
            $table->dropConstrainedForeignId('corrects_submission_id');
            $table->dropConstrainedForeignId('submitted_by');
            $table->index(['location_id', 'status']);
        });

        Schema::dropIfExists('assignments');

        // 4. Remove SPG accounts and their sessions.
        $spgIds = DB::table('users')->where('role', 'spg')->pluck('id');
        DB::table(config('session.table', 'sessions'))->whereIn('user_id', $spgIds)->delete();
        DB::table('users')->whereIn('id', $spgIds)->delete();
    }

    public function down(): void
    {
        // Not reversible: SPG accounts, assignments and entry authorship are deleted in up().
        throw new RuntimeException('The switch to public location forms cannot be rolled back. Restore a database backup instead.');
    }
};
