<?php

namespace App\Http\Controllers;

use App\Game\LevelPresenter;
use App\Models\Player;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __invoke(Request $request, LevelPresenter $presenter): Response
    {
        $player = Player::query()->find((int) $request->session()->get('player_id'));

        return Inertia::render('Game', [
            'chapters' => $presenter->chapters(),
            'player' => $player ? ['name' => $player->name, 'token' => $player->token] : null,
            'completions' => $player ? $player->completions()->pluck('stars', 'level_id') : (object) [],
            'topHighscores' => $this->leaderboard()
                ->limit(5)
                ->get()
                ->map(fn (Player $entry) => ['name' => $entry->name, 'stars' => (int) $entry->getAttribute('stars')]),
            'highscores' => $this->leaderboard()
                ->paginate(20)
                ->withQueryString()
                ->through(fn (Player $entry) => ['name' => $entry->name, 'stars' => (int) $entry->getAttribute('stars')]),
        ]);
    }

    /**
     * Players ranked by total stars; ties go to whoever got there first.
     *
     * @return Builder<Player>
     */
    private function leaderboard(): Builder
    {
        return Player::query()
            ->join('level_completions', 'level_completions.player_id', '=', 'players.id')
            ->groupBy('players.id', 'players.name')
            ->select('players.name')
            ->selectRaw('SUM(level_completions.stars) as stars')
            ->selectRaw('MAX(level_completions.updated_at) as latest_completion')
            ->orderByDesc('stars')
            ->orderBy('latest_completion');
    }
}
