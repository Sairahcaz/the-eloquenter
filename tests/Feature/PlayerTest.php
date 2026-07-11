<?php

use App\Models\Player;
use Illuminate\Support\Str;

it('creates a player and stores it in the session', function () {
    $this->post(route('players.store'), ['name' => 'Zach'])
        ->assertRedirect()
        ->assertSessionHas('player_id');

    $player = Player::sole();

    expect($player->name)->toBe('Zach')
        ->and($player->token)->toBeUuid()
        ->and(session('player_id'))->toBe($player->id);
});

it('resumes an existing player by token', function () {
    $player = Player::factory()->create(['name' => 'Zach']);

    $this->post(route('players.store'), ['name' => 'Zach', 'token' => $player->token]);

    expect(Player::count())->toBe(1)
        ->and(session('player_id'))->toBe($player->id);
});

it('creates a fresh player for an unknown token', function () {
    Player::factory()->create(['name' => 'Zach']);

    $this->post(route('players.store'), ['name' => 'Zach', 'token' => Str::uuid()->toString()]);

    expect(Player::count())->toBe(2);
});

it('rejects invalid join payloads', function (array $payload) {
    $this->post(route('players.store'), $payload)->assertSessionHasErrors();
})->with([
    'missing name' => [[]],
    'name too long' => [['name' => str_repeat('x', 31)]],
    'malformed token' => [['name' => 'Zach', 'token' => 'not-a-uuid']],
]);
