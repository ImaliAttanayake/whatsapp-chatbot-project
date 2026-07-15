<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->string('uuid')->nullable()->index();     // msg uuid from API
            $table->enum('direction', ['in', 'out'])->index();
            $table->text('body')->nullable();

            $table->timestamp('sent_at')->nullable()->index();
            $table->json('raw')->nullable();

            $table->timestamps();
            $table->unique(['contact_id', 'uuid']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('messages');
    }
};
