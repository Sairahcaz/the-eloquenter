<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders the game with all chapters', function () {
    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Game')
            ->has('chapters', 6)
            ->has('chapters.0.levels', 5)
            ->where('chapters.0.levels.0.id', 'c1-l1')
            ->where('chapters.0.levels.0.mode', 'connect')
            ->has('chapters.0.levels.0.tables', 2)
        );
});
