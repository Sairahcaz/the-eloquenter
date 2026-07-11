<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

/**
 * @property int $id
 * @property string $name
 */
class Mechanic extends Model
{
    public $timestamps = false;

    /**
     * @return HasOneThrough<Owner, Car, $this>
     */
    public function carOwner(): HasOneThrough
    {
        return $this->hasOneThrough(Owner::class, Car::class);
    }
}
