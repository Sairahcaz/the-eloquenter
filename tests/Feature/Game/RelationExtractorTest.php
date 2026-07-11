<?php

use App\Game\ExtractedRelation;
use App\Game\RelationExtractor;
use App\Game\RelationType;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Phone;
use App\Models\Post;
use App\Models\Project;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;

/**
 * @return list<string>
 */
function connectionStrings(ExtractedRelation $extraction): array
{
    return array_map(
        fn ($connection) => "{$connection->fromTable}.{$connection->fromColumn} -> {$connection->toTable}.{$connection->toColumn}",
        $extraction->connections,
    );
}

it('extracts relation structure from real model methods', function (
    string $modelClass,
    string $method,
    array $morphTargets,
    RelationType $expectedType,
    array $expectedTables,
    array $expectedConnections,
) {
    $extraction = (new RelationExtractor)->extract($modelClass, $method, $morphTargets);

    expect($extraction->type)->toBe($expectedType)
        ->and($extraction->tables)->toBe($expectedTables)
        ->and(connectionStrings($extraction))->toBe($expectedConnections);
})->with([
    'hasOne' => [
        User::class, 'phone', [],
        RelationType::HasOne,
        ['users', 'phones'],
        ['phones.user_id -> users.id'],
    ],
    'hasMany' => [
        User::class, 'posts', [],
        RelationType::HasMany,
        ['users', 'posts'],
        ['posts.user_id -> users.id'],
    ],
    'hasMany with non-conventional foreign key' => [
        Customer::class, 'orders', [],
        RelationType::HasMany,
        ['customers', 'orders'],
        ['orders.customer_ref -> customers.id'],
    ],
    'belongsTo' => [
        Phone::class, 'user', [],
        RelationType::BelongsTo,
        ['phones', 'users'],
        ['phones.user_id -> users.id'],
    ],
    'belongsToMany' => [
        User::class, 'roles', [],
        RelationType::BelongsToMany,
        ['users', 'role_user', 'roles'],
        ['role_user.user_id -> users.id', 'role_user.role_id -> roles.id'],
    ],
    'hasOneThrough' => [
        Mechanic::class, 'carOwner', [],
        RelationType::HasOneThrough,
        ['mechanics', 'cars', 'owners'],
        ['cars.mechanic_id -> mechanics.id', 'owners.car_id -> cars.id'],
    ],
    'hasManyThrough' => [
        Project::class, 'deployments', [],
        RelationType::HasManyThrough,
        ['projects', 'environments', 'deployments'],
        ['environments.project_id -> projects.id', 'deployments.environment_id -> environments.id'],
    ],
    'morphOne' => [
        User::class, 'image', [],
        RelationType::MorphOne,
        ['users', 'images'],
        ['images.imageable_id -> users.id'],
    ],
    'morphMany' => [
        Video::class, 'reactions', [],
        RelationType::MorphMany,
        ['videos', 'reactions'],
        ['reactions.reactable_id -> videos.id'],
    ],
    'morphTo' => [
        Reaction::class, 'reactable', [Post::class, Video::class],
        RelationType::MorphTo,
        ['reactions', 'posts', 'videos'],
        ['reactions.reactable_id -> posts.id', 'reactions.reactable_id -> videos.id'],
    ],
    'morphToMany' => [
        Post::class, 'tags', [],
        RelationType::MorphToMany,
        ['posts', 'taggables', 'tags'],
        ['taggables.taggable_id -> posts.id', 'taggables.tag_id -> tags.id'],
    ],
    'morphToMany with additional owners' => [
        Post::class, 'tags', [Post::class, Video::class],
        RelationType::MorphToMany,
        ['posts', 'videos', 'taggables', 'tags'],
        ['taggables.taggable_id -> posts.id', 'taggables.taggable_id -> videos.id', 'taggables.tag_id -> tags.id'],
    ],
]);

it('marks morph type columns and foreign keys for badges', function () {
    $extraction = (new RelationExtractor)->extract(Video::class, 'reactions');

    expect($extraction->columnKeys)->toBe([
        'reactions' => ['reactable_id' => 'foreign', 'reactable_type' => 'morph'],
    ]);
});

it('exposes the pivot table for many to many relations', function () {
    $extraction = (new RelationExtractor)->extract(User::class, 'roles');

    expect($extraction->pivotTable)->toBe('role_user')
        ->and($extraction->columnKeys)->toBe([
            'role_user' => ['user_id' => 'foreign', 'role_id' => 'foreign'],
        ]);
});

it('rejects morphTo without explicit targets', function () {
    (new RelationExtractor)->extract(Reaction::class, 'reactable');
})->throws(InvalidArgumentException::class, 'morphTo relations need explicit morph targets');

it('rejects methods that are not relations', function () {
    (new RelationExtractor)->extract(User::class, 'getTable');
})->throws(InvalidArgumentException::class, 'does not return an Eloquent relation');
