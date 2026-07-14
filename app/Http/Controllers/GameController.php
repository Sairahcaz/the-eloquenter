<?php

namespace App\Http\Controllers;

use App\Game\LevelPresenter;
use App\Models\Player;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;

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
     * Ranked by star total, total play time breaking ties (faster wins).
     * One row per distinct (stars, seconds) pair so still-tied players
     * share a rank, with their names aggregated in random order on every
     * load.
     */
    private function leaderboard(): Builder
    {
        $totals = Player::query()
            ->join('level_completions', 'level_completions.player_id', '=', 'players.id')
            ->groupBy('players.id', 'players.name')
            ->select('players.name')
            ->selectRaw('SUM(level_completions.stars) as stars')
            ->selectRaw('SUM(COALESCE(level_completions.duration_seconds, 0)) as seconds');

        // json_group_array needs SQLite >= 3.44 for ORDER BY inside aggregates.
        $namesAggregate = DB::connection()->getDriverName() === 'sqlite'
            ? 'json_group_array(name ORDER BY random())'
            : 'json_agg(name ORDER BY random())';

        return DB::query()
            ->fromSub($totals, 'totals')
            ->groupBy('stars', 'seconds')
            ->select('stars', 'seconds')
            ->selectRaw("{$namesAggregate} as names")
            ->orderByDesc('stars')
            ->orderBy('seconds');
    }

    /**
     * @return array{names: list<string>, stars: int, seconds: int}
     */
    private function toEntry(stdClass $row): array
    {
        return [
            'names' => json_decode((string) $row->names),
            'stars' => (int) $row->stars,
            'seconds' => (int) $row->seconds,
        ];
    }
}
