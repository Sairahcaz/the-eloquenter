<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('level_attempts', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('made_connections');
        });

        Schema::table('level_completions', function (Blueprint $table) {
            $table->unsignedInteger('duration_seconds')->nullable()->after('stars');
        });

        $this->backfillDurations();
    }

    public function down(): void
    {
        Schema::table('level_attempts', function (Blueprint $table) {
            $table->dropColumn('started_at');
        });

        Schema::table('level_completions', function (Blueprint $table) {
            $table->dropColumn('duration_seconds');
        });
    }

    /**
     * Completions from before time scoring never measured a duration.
     * Approximate each as the gap since the player's previous completion,
     * the first one counting from the player's registration.
     */
    private function backfillDurations(): void
    {
        DB::table('players')->orderBy('id')->each(function (object $player) {
            $previous = Carbon::parse($player->created_at);

            DB::table('level_completions')
                ->where('player_id', $player->id)
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'created_at'])
                ->each(function (object $completion) use (&$previous) {
                    $completedAt = Carbon::parse($completion->created_at);

                    DB::table('level_completions')
                        ->where('id', $completion->id)
                        ->update(['duration_seconds' => max(0, (int) $previous->diffInSeconds($completedAt))]);

                    $previous = $completedAt;
                });
        });
    }
};
