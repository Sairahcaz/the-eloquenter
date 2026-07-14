<?php

namespace App\Models;

use Database\Factories\LevelAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The running state of a player inside one level: mistakes and hint usage
 * feed the star rating, made connections track connect-mode progress.
 *
 * @property int $id
 * @property int $player_id
 * @property string $level_id
 * @property int $mistakes
 * @property bool $hint_used
 * @property list<array{from: array{table: string, column: string}, to: array{table: string, column: string}}> $made_connections
 * @property Carbon|null $started_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['player_id', 'level_id', 'mistakes', 'hint_used', 'made_connections', 'started_at'])]
class LevelAttempt extends Model
{
    /** @use HasFactory<LevelAttemptFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'hint_used' => 'boolean',
            'made_connections' => 'array',
            'started_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
