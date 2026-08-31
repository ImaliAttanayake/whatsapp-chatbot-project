<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile')->unique(); // e.g. 94714288332
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('contacts');
    }
};
