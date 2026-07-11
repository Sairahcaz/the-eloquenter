<?php

namespace App\Game;

enum RelationType: string
{
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsTo = 'belongsTo';
    case BelongsToMany = 'belongsToMany';
    case HasOneThrough = 'hasOneThrough';
    case HasManyThrough = 'hasManyThrough';
    case MorphOne = 'morphOne';
    case MorphMany = 'morphMany';
    case MorphTo = 'morphTo';
    case MorphToMany = 'morphToMany';
}
