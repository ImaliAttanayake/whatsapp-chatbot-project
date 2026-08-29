<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('assigned_admin_id')
                ->nullable()
                ->after('human_handoff_assigned_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('assigned_at')->nullable()->after('assigned_admin_id');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_admin_id');
            $table->dropColumn('assigned_at');
        });
    }
};
