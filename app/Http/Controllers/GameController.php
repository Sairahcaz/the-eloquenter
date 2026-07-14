<?php

namespace App\Http\Controllers;

use App\Game\LevelPresenter;
use App\Models\Player;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                ->map($this->toEntry(...)),
            'highscores' => $this->leaderboard()
                ->paginate(20)
                ->withQueryString()
                ->through($this->toEntry(...)),
        ]);
    }

    /**
     * One row per distinct star total (tied players share a rank), with
     * the tied names aggregated in the order they reached that total.
     */
    private function leaderboard(): Builder
    {
        $totals = Player::query()
            ->join('level_completions', 'level_completions.player_id', '=', 'players.id')
            ->groupBy('players.id', 'players.name')
            ->select('players.name')
            ->selectRaw('SUM(level_completions.stars) as stars')
            ->selectRaw('MAX(level_completions.updated_at) as latest_completion');

        // json_group_array needs SQLite >= 3.44 for ORDER BY inside aggregates.
        $namesAggregate = DB::connection()->getDriverName() === 'sqlite'
            ? 'json_group_array(name ORDER BY latest_completion, name)'
            : 'json_agg(name ORDER BY latest_completion, name)';

        return DB::query()
            ->fromSub($totals, 'totals')
            ->groupBy('stars')
            ->select('stars')
            ->selectRaw("{$namesAggregate} as names")
            ->orderByDesc('stars');
    }

    /**
     * @return array{names: list<string>, stars: int}
     */
    private function toEntry(object $row): array
    {
        return ['names' => json_decode((string) $row->names), 'stars' => (int) $row->stars];
    }
}
