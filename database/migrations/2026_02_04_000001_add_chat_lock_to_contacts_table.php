<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('locked_by_user_id')->nullable()->after('last_synced_at')->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable()->after('locked_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('locked_by_user_id');
            $table->dropColumn('locked_at');
        });
    }
};
