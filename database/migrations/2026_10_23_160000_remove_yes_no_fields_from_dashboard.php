<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Yes/No answers now only feed the qualification count; they no longer get a dashboard chart.
     */
    public function up(): void
    {
        DB::table('form_fields')->where('type', 'yes_no')->update(['show_on_dashboard' => false]);
        DB::table('submission_field_values')->where('field_type', 'yes_no')->delete();
    }

    public function down(): void
    {
        // The flag and the derived dashboard rows are not restored.
    }
};
