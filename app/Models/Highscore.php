<?php

namespace App\Models;

use Database\Factories\HighscoreFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $stars
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'stars'])]
class Highscore extends Model
{
    /** @use HasFactory<HighscoreFactory> */
    use HasFactory;
}
