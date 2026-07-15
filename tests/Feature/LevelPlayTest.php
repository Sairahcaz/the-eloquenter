<?php

use App\Game\CodeReconstructor;
use App\Game\Levels;
use App\Game\RelationExtractor;
use App\Models\LevelCompletion;
use App\Models\Player;

/**
 * @return array{from: array{table: string, column: string}, to: array{table: string, column: string}}
 */
function expectedConnectionFor(string $levelId, int $index = 0): array
{
    $level = Levels::find($levelId);

    return app(RelationExtractor::class)
        ->extract($level->model, $level->method, $level->morphTargets)
        ->connections[$index]
        ->toArray();
}

/**
 * @return array<string, string>
 */
function codeAnswersFor(string $levelId): array
{
    $level = Levels::find($levelId);
    $parts = app(CodeReconstructor::class)->reconstruct($level->model, $level->method)['codeParts'];

    return collect($parts)->filter(fn ($part) => is_array($part))->mapWithKeys(
        fn (array $part) => [$part['id'] => $part['answer']],
    )->all();
}

function playerAt(string $levelId): Player
{
    $player = Player::factory()->create();

    foreach (Levels::all() as $level) {
        if ($level->id === $levelId) {
            break;
        }

        LevelCompletion::factory()->create([
            'player_id' => $player->id,
            'level_id' => $level->id,
            'stars' => 3,
        ]);
    }

    test()->withSession(['player_id' => $player->id]);

    return $player;
}

it('rejects gameplay without a player session', function () {
    $this->postJson(route('levels.connections', 'c1-l1'), [
        'from' => ['table' => 'users', 'column' => 'id'],
        'to' => ['table' => 'phones', 'column' => 'user_id'],
    ])->assertForbidden();
});

it('rejects gameplay on locked levels', function () {
    playerAt('c1-l1');

    $this->postJson(route('levels.guess', 'c1-l2'), ['choice' => 'hasOne'])
        ->assertForbidden();
});

it('rejects unknown levels and mismatched modes', function () {
    playerAt('c1-l1');

    $this->postJson(route('levels.attempt', 'nope'))->assertNotFound();
    $this->postJson(route('levels.guess', 'c1-l1'), ['choice' => 'hasOne'])->assertNotFound();
});

it('solves a connect level flawlessly for three stars', function () {
    $player = playerAt('c1-l1');

    $this->postJson(route('levels.attempt', 'c1-l1'))->assertNoContent();

    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertSuccessful()
        ->assertJson(['correct' => true, 'solved' => true, 'stars' => 3, 'relation' => 'hasOne']);

    $this->assertDatabaseHas('level_completions', [
        'player_id' => $player->id,
        'level_id' => 'c1-l1',
        'stars' => 3,
    ]);
});

it('accepts a reversed connection direction', function () {
    playerAt('c1-l1');

    $connection = expectedConnectionFor('c1-l1');

    $this->postJson(route('levels.connections', 'c1-l1'), [
        'from' => $connection['to'],
        'to' => $connection['from'],
    ])->assertJson(['correct' => true, 'solved' => true]);
});

it('deducts one star per wrong connection, never below one', function () {
    playerAt('c1-l1');

    $wrong = [
        'from' => ['table' => 'users', 'column' => 'name'],
        'to' => ['table' => 'phones', 'column' => 'id'],
    ];

    foreach (range(1, 4) as $attempt) {
        $this->postJson(route('levels.connections', 'c1-l1'), $wrong)
            ->assertJson(['correct' => false, 'solved' => false]);
    }

    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['correct' => true, 'solved' => true, 'stars' => 1]);
});

it('caps the rating at two stars after a hint', function () {
    playerAt('c1-l1');

    $this->postJson(route('levels.hint', 'c1-l1'))
        ->assertSuccessful()
        ->assertJsonStructure(['hint']);

    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['stars' => 2]);
});

it('resets mistakes and hints when a new attempt starts', function () {
    playerAt('c1-l1');

    $this->postJson(route('levels.hint', 'c1-l1'));
    $this->postJson(route('levels.connections', 'c1-l1'), [
        'from' => ['table' => 'users', 'column' => 'name'],
        'to' => ['table' => 'phones', 'column' => 'id'],
    ]);

    $this->postJson(route('levels.attempt', 'c1-l1'))->assertNoContent();

    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['stars' => 3]);
});

it('keeps the best star rating across replays', function () {
    $player = playerAt('c1-l1');

    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['stars' => 3]);

    $this->postJson(route('levels.attempt', 'c1-l1'));
    $this->postJson(route('levels.hint', 'c1-l1'));
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['stars' => 2]);

    $this->assertDatabaseHas('level_completions', [
        'player_id' => $player->id,
        'level_id' => 'c1-l1',
        'stars' => 3,
    ]);
});

it('records the solve time from attempt start', function () {
    $player = playerAt('c1-l1');

    $this->freezeSecond();
    $this->postJson(route('levels.attempt', 'c1-l1'))->assertNoContent();

    $this->travel(42)->seconds();
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'));

    $this->assertDatabaseHas('level_completions', [
        'player_id' => $player->id,
        'level_id' => 'c1-l1',
        'duration_seconds' => 42,
    ]);
});

it('keeps the faster time across equal-star replays', function () {
    $player = playerAt('c1-l1');

    $this->freezeSecond();
    $this->postJson(route('levels.attempt', 'c1-l1'));
    $this->travel(42)->seconds();
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'));

    $this->postJson(route('levels.attempt', 'c1-l1'));
    $this->travel(10)->seconds();
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'));

    $this->postJson(route('levels.attempt', 'c1-l1'));
    $this->travel(99)->seconds();
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'));

    $this->assertDatabaseHas('level_completions', [
        'player_id' => $player->id,
        'level_id' => 'c1-l1',
        'stars' => 3,
        'duration_seconds' => 10,
    ]);
});

it('takes the time of the better-starred run even when slower', function () {
    $player = playerAt('c1-l1');

    $this->freezeSecond();
    $this->postJson(route('levels.attempt', 'c1-l1'));
    $this->postJson(route('levels.hint', 'c1-l1'));
    $this->travel(10)->seconds();
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['stars' => 2]);

    $this->postJson(route('levels.attempt', 'c1-l1'));
    $this->travel(99)->seconds();
    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'))
        ->assertJson(['stars' => 3]);

    $this->assertDatabaseHas('level_completions', [
        'player_id' => $player->id,
        'level_id' => 'c1-l1',
        'stars' => 3,
        'duration_seconds' => 99,
    ]);
});

it('solves a multi-connection level only after every connection', function () {
    playerAt('c3-l1');

    $this->postJson(route('levels.connections', 'c3-l1'), expectedConnectionFor('c3-l1', 0))
        ->assertJson(['correct' => true, 'solved' => false, 'made' => 1]);

    $this->postJson(route('levels.connections', 'c3-l1'), expectedConnectionFor('c3-l1', 1))
        ->assertJson(['correct' => true, 'solved' => true, 'stars' => 3, 'made' => 2]);
});

it('ignores a repeated correct connection', function () {
    playerAt('c3-l1');

    $this->postJson(route('levels.connections', 'c3-l1'), expectedConnectionFor('c3-l1', 0));
    $this->postJson(route('levels.connections', 'c3-l1'), expectedConnectionFor('c3-l1', 0))
        ->assertJson(['correct' => true, 'solved' => false, 'made' => 1]);
});

it('reveals the solution only after completing the level', function () {
    playerAt('c1-l1');

    $this->getJson(route('levels.solution', 'c1-l1'))->assertForbidden();

    $this->postJson(route('levels.connections', 'c1-l1'), expectedConnectionFor('c1-l1'));

    $this->getJson(route('levels.solution', 'c1-l1'))
        ->assertSuccessful()
        ->assertJson([
            'connections' => [expectedConnectionFor('c1-l1')],
            'relation' => 'hasOne',
            'answers' => null,
        ]);
});

it('includes the blank answers in a code level solution', function () {
    $player = playerAt('c1-l3');

    LevelCompletion::factory()->create([
        'player_id' => $player->id,
        'level_id' => 'c1-l3',
        'stars' => 3,
    ]);

    $this->getJson(route('levels.solution', 'c1-l3'))
        ->assertSuccessful()
        ->assertJson([
            'relation' => 'hasOne',
            'answers' => codeAnswersFor('c1-l3'),
        ]);
});

it('judges guesses server-side', function () {
    $player = playerAt('c1-l2');

    $this->postJson(route('levels.guess', 'c1-l2'), ['choice' => 'belongsTo'])
        ->assertJson(['correct' => false, 'stars' => null]);

    $this->postJson(route('levels.guess', 'c1-l2'), ['choice' => 'hasOne'])
        ->assertJson(['correct' => true, 'stars' => 2, 'relation' => 'hasOne']);

    $this->assertDatabaseHas('level_completions', [
        'player_id' => $player->id,
        'level_id' => 'c1-l2',
        'stars' => 2,
    ]);
});

it('rejects a guess outside the relation types', function () {
    playerAt('c1-l2');

    $this->postJson(route('levels.guess', 'c1-l2'), ['choice' => 'hasLots'])
        ->assertUnprocessable();
});

it('judges code answers server-side', function () {
    playerAt('c1-l3');

    $answers = codeAnswersFor('c1-l3');
    $wrong = array_map(fn () => 'nope()', $answers);

    $this->postJson(route('levels.code', 'c1-l3'), ['answers' => $wrong])
        ->assertJson(['correct' => false, 'stars' => null])
        ->assertJsonCount(count($answers), 'wrongBlanks');

    $response = $this->postJson(route('levels.code', 'c1-l3'), ['answers' => $answers])
        ->assertJson(['correct' => true, 'stars' => 2, 'relation' => 'hasOne']);

    expect($response->json('statement'))->toContain('hasOne');
});
