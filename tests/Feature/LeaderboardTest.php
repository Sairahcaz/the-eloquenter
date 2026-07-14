<?php

use App\Models\LevelCompletion;
use App\Models\Player;
use Inertia\Testing\AssertableInertia as Assert;

function playerWithStars(string $name, array $starsPerLevel): Player
{
    $player = Player::factory()->create(['name' => $name]);

    foreach ($starsPerLevel as $levelId => $stars) {
        LevelCompletion::factory()->create([
            'player_id' => $player->id,
            'level_id' => $levelId,
            'stars' => $stars,
        ]);
    }

    return $player;
}

function playerWithStarTotal(string $name, int $total): Player
{
    $starsPerLevel = array_fill_keys(
        array_map(fn (int $level): string => "c1-l{$level}", range(1, $total)),
        1,
    );

    return playerWithStars($name, $starsPerLevel);
}

it('ranks players by their summed stars', function () {
    playerWithStars('Runner-up', ['c1-l1' => 2, 'c1-l2' => 3]);
    playerWithStars('Champion', ['c1-l1' => 3, 'c1-l2' => 3]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game')
            ->where('topHighscores.0.names', ['Champion'])
            ->where('topHighscores.0.stars', 6)
            ->where('topHighscores.1.names', ['Runner-up'])
            ->where('topHighscores.1.stars', 5)
        );
});

it('groups tied players into a shared rank', function () {
    playerWithStars('Alice', ['c1-l1' => 3, 'c1-l2' => 2]);
    playerWithStars('Bob', ['c1-l1' => 2, 'c1-l2' => 3]);
    playerWithStars('Carol', ['c1-l1' => 3]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('topHighscores', 2)
            ->where('topHighscores.0.names', fn ($names) => collect($names)->sort()->values()->all() === ['Alice', 'Bob'])
            ->where('topHighscores.0.stars', 5)
            ->where('topHighscores.1.names', ['Carol'])
            ->where('topHighscores.1.stars', 3)
        );
});

it('keeps players without completions off the board', function () {
    Player::factory()->create(['name' => 'Lurker']);
    playerWithStars('Active', ['c1-l1' => 1]);

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('topHighscores', 1)
            ->where('topHighscores.0.names', ['Active'])
        );
});

it('limits the start screen board to five entries', function () {
    foreach (range(1, 7) as $i) {
        playerWithStarTotal("Player {$i}", $i);
    }

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->has('topHighscores', 5));
});

it('paginates the full leaderboard by twenty', function () {
    foreach (range(1, 25) as $i) {
        playerWithStarTotal("Player {$i}", $i);
    }

    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('highscores.data', 20)
            ->where('highscores.per_page', 20)
            ->where('highscores.last_page', 2)
        );

    $this->get(route('home', ['page' => 2]))
        ->assertInertia(fn (Assert $page) => $page
            ->has('highscores.data', 5)
            ->where('highscores.current_page', 2)
        );
});

it('shares the session player and their completions', function () {
    $player = playerWithStars('Zach', ['c1-l1' => 3, 'c1-l2' => 2]);

    $this->withSession(['player_id' => $player->id])
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('player.name', 'Zach')
            ->where('player.token', $player->token)
            ->where('completions.c1-l1', 3)
            ->where('completions.c1-l2', 2)
        );
});

it('shares no player without a session', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page->where('player', null));
});
