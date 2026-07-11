<?php

namespace App\Http\Controllers;

use App\Game\LevelPresenter;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __invoke(LevelPresenter $presenter): Response
    {
        return Inertia::render('Game', [
            'chapters' => $presenter->chapters(),
        ]);
    }
}
