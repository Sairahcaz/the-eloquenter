<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('level_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('player_id')->constrained()->cascadeOnDelete();
            $table->string('level_id', 20);
            $table->unsignedSmallInteger('mistakes')->default(0);
            $table->boolean('hint_used')->default(false);
            $table->json('made_connections');
            $table->timestamps();

            $table->unique(['player_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('level_attempts');
    }
};
