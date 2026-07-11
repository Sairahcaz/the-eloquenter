<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $url
 * @property int $imageable_id
 * @property string $imageable_type
 */
class Image extends Model
{
    public $timestamps = false;

    /**
     * @return MorphTo<Model, $this>
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}
