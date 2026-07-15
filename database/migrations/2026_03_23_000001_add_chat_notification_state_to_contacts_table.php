<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->timestamp('last_message_at')->nullable()->after('last_read_at');
            $table->string('last_message_direction', 8)->nullable()->after('last_message_at');
            $table->text('last_message_preview')->nullable()->after('last_message_direction');
            $table->timestamp('last_inbound_message_at')->nullable()->after('last_message_preview');
            $table->string('last_inbound_message_key', 191)->nullable()->after('last_inbound_message_at');
            $table->text('last_inbound_message_preview')->nullable()->after('last_inbound_message_key');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn([
                'last_message_at',
                'last_message_direction',
                'last_message_preview',
                'last_inbound_message_at',
                'last_inbound_message_key',
                'last_inbound_message_preview',
            ]);
        });
    }
};
