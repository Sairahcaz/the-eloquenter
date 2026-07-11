<?php

use App\Models\Highscore;
use Inertia\Testing\AssertableInertia as Assert;

it('stores a new highscore', function () {
    $this->post(route('highscores.store'), ['name' => 'Zach', 'stars' => 12])
        ->assertRedirect();

    $this->assertDatabaseHas('highscores', ['name' => 'Zach', 'stars' => 12]);
});

it('keeps the best score per player', function () {
    Highscore::factory()->create(['name' => 'Zach', 'stars' => 20]);

    $this->post(route('highscores.store'), ['name' => 'Zach', 'stars' => 12]);

    $this->assertDatabaseHas('highscores', ['name' => 'Zach', 'stars' => 20]);
    $this->assertDatabaseCount('highscores', 1);

    $this->post(route('highscores.store'), ['name' => 'Zach', 'stars' => 33]);

    $this->assertDatabaseHas('highscores', ['name' => 'Zach', 'stars' => 33]);
});

it('rejects invalid submissions', function (array $payload) {
    $this->post(route('highscores.store'), $payload)->assertSessionHasErrors();
})->with([
    'missing name' => [['stars' => 5]],
    'name too long' => [['name' => str_repeat('x', 31), 'stars' => 5]],
    'negative stars' => [['name' => 'Zach', 'stars' => -1]],
    'more stars than the game has' => [['name' => 'Zach', 'stars' => 70]],
]);

it('shares the top ten highscores ordered by stars', function () {
    Highscore::factory()->count(12)->create();
    $best = Highscore::factory()->create(['name' => 'TopDog', 'stars' => 69]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game')
            ->has('highscores', 10)
            ->where('highscores.0.name', $best->name)
            ->where('highscores.0.stars', 69)
        );
});
