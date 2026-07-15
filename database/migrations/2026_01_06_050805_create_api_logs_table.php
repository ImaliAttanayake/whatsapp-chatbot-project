<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service')->default('slt_whatsapp');
            $table->string('endpoint');
            $table->string('method');
            $table->integer('status')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('api_logs');
    }
};
