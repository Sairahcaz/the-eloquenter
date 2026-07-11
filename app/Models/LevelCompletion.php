<?php

namespace App\Models;

use Database\Factories\LevelCompletionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $player_id
 * @property string $level_id
 * @property int $stars
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['player_id', 'level_id', 'stars'])]
class LevelCompletion extends Model
{
    /** @use HasFactory<LevelCompletionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Player, $this>
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }
}
