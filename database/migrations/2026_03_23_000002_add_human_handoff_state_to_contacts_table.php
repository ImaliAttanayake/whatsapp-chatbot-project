<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->unsignedInteger('unread_message_count')->default(0)->after('last_inbound_message_preview');
            $table->boolean('human_handoff_active')->default(false)->after('unread_message_count');
            $table->timestamp('human_handoff_requested_at')->nullable()->after('human_handoff_active');
            $table->string('human_handoff_message_key', 191)->nullable()->after('human_handoff_requested_at');
            $table->text('human_handoff_message_preview')->nullable()->after('human_handoff_message_key');
            $table->foreignId('human_handoff_assigned_user_id')->nullable()->after('human_handoff_message_preview')->constrained('users')->nullOnDelete();
            $table->timestamp('human_handoff_assigned_at')->nullable()->after('human_handoff_assigned_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('human_handoff_assigned_user_id');
            $table->dropColumn([
                'unread_message_count',
                'human_handoff_active',
                'human_handoff_requested_at',
                'human_handoff_message_key',
                'human_handoff_message_preview',
                'human_handoff_assigned_at',
            ]);
        });
    }
};
