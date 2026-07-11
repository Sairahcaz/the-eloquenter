<?php

namespace App\Models;

use Database\Factories\PlayerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'token'])]
class Player extends Model
{
    /** @use HasFactory<PlayerFactory> */
    use HasFactory;

    /**
     * @return HasMany<LevelAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(LevelAttempt::class);
    }

    /**
     * @return HasMany<LevelCompletion, $this>
     */
    public function completions(): HasMany
    {
        return $this->hasMany(LevelCompletion::class);
    }
}
