<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'human_handoff_status')) {
                $table->string('human_handoff_status', 32)->default('open')->after('human_handoff_active');
            }

            if (!Schema::hasColumn('contacts', 'bot_paused')) {
                $table->boolean('bot_paused')->default(false)->after('human_handoff_status');
            }
        });

        DB::table('contacts')
            ->where(function ($query) {
                $query->where('human_handoff_active', 1)
                    ->orWhereNotNull('human_handoff_requested_at')
                    ->orWhereNotNull('human_handoff_assigned_user_id');
            })
            ->update([
                'human_handoff_status' => DB::raw("CASE WHEN human_handoff_assigned_user_id IS NOT NULL THEN 'assigned_to_agent' ELSE 'needs_human' END"),
                'bot_paused' => 1,
                'human_handoff_active' => 1,
            ]);
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (Schema::hasColumn('contacts', 'bot_paused')) {
                $table->dropColumn('bot_paused');
            }

            if (Schema::hasColumn('contacts', 'human_handoff_status')) {
                $table->dropColumn('human_handoff_status');
            }
        });
    }
};
