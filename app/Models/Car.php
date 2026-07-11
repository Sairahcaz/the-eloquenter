<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $mechanic_id
 * @property string $model
 */
class Car extends Model
{
    public $timestamps = false;

    /**
     * @return BelongsTo<Mechanic, $this>
     */
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Mechanic::class);
    }
}
