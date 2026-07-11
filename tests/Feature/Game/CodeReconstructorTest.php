<?php

use App\Game\CodeReconstructor;
use App\Game\RelationExtractor;
use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Phone;
use App\Models\Post;
use App\Models\Project;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;

function reconstructor(): CodeReconstructor
{
    return new CodeReconstructor(new RelationExtractor);
}

/**
 * The actual `return ...;` statement of a relation method, whitespace-normalized.
 */
function actualMethodStatement(string $modelClass, string $method): string
{
    $reflection = new ReflectionMethod($modelClass, $method);
    $lines = file($reflection->getFileName());
    $source = implode('', array_slice($lines, $reflection->getStartLine(), $reflection->getEndLine() - $reflection->getStartLine()));

    preg_match('/return\s.*?;/s', $source, $matches);

    return preg_replace('/\s+/', ' ', $matches[0]);
}

it('reconstructs canonical relation statements', function (string $modelClass, string $method, string $expected) {
    $result = reconstructor()->reconstruct($modelClass, $method);

    expect(CodeReconstructor::statement($result['codeParts']))->toBe($expected);
})->with([
    'conventional hasOne omits key arguments' => [User::class, 'phone', 'return $this->hasOne(Phone::class);'],
    'non-conventional foreign key is emitted' => [Customer::class, 'orders', "return \$this->hasMany(Order::class, 'customer_ref');"],
    'conventional belongsTo omits key arguments' => [Post::class, 'user', 'return $this->belongsTo(User::class);'],
    'conventional belongsToMany omits the pivot table' => [User::class, 'roles', 'return $this->belongsToMany(Role::class);'],
    'through relations list far then through class' => [Mechanic::class, 'carOwner', 'return $this->hasOneThrough(Owner::class, Car::class);'],
    'morphMany always emits the morph name' => [Video::class, 'reactions', "return \$this->morphMany(Reaction::class, 'reactable');"],
    'morphTo takes no arguments' => [Reaction::class, 'reactable', 'return $this->morphTo();'],
    'morphToMany always emits the morph name' => [Post::class, 'tags', "return \$this->morphToMany(Tag::class, 'taggable');"],
]);

it('matches the real method source, so reconstruction cannot drift', function (string $modelClass, string $method) {
    $result = reconstructor()->reconstruct($modelClass, $method);

    expect(CodeReconstructor::statement($result['codeParts']))->toBe(actualMethodStatement($modelClass, $method));
})->with([
    [User::class, 'phone'],
    [User::class, 'posts'],
    [User::class, 'roles'],
    [Phone::class, 'user'],
    [Post::class, 'comments'],
    [Customer::class, 'orders'],
    [Mechanic::class, 'carOwner'],
    [Project::class, 'deployments'],
    [Video::class, 'reactions'],
    [Reaction::class, 'reactable'],
    [Post::class, 'tags'],
]);

it('always includes the answer among unique options for every blank', function () {
    $pairs = [
        [User::class, 'phone'],
        [Customer::class, 'orders'],
        [Mechanic::class, 'carOwner'],
        [Video::class, 'reactions'],
        [Post::class, 'tags'],
    ];

    foreach ($pairs as [$modelClass, $method]) {
        $result = reconstructor()->reconstruct($modelClass, $method);
        $blanks = array_values(array_filter($result['codeParts'], fn ($part) => is_array($part)));

        expect($blanks)->not->toBeEmpty();

        foreach ($blanks as $blank) {
            expect($blank['options'])->toContain($blank['answer'])
                ->and($blank['options'])->toHaveCount(count(array_unique($blank['options'])))
                ->and(count($blank['options']))->toBeGreaterThanOrEqual(3);
        }
    }
});
