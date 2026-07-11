<?php

namespace App\Http\Controllers;

use App\Game\LevelPresenter;
use App\Models\Highscore;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __invoke(LevelPresenter $presenter): Response
    {
        return Inertia::render('Game', [
            'chapters' => $presenter->chapters(),
            'highscores' => Highscore::query()
                ->orderByDesc('stars')
                ->orderBy('updated_at')
                ->limit(10)
                ->get(['name', 'stars']),
        ]);
    }
}
