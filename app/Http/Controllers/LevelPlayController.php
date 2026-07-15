<?php

namespace App\Http\Controllers;

use App\Game\LevelDefinition;
use App\Game\Levels;
use App\Game\Mode;
use App\Game\Referee;
use App\Http\Requests\CodeAttemptRequest;
use App\Http\Requests\ConnectAttemptRequest;
use App\Http\Requests\GuessAttemptRequest;
use App\Models\Player;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Gameplay endpoints. Every move is judged server-side so the client never
 * needs to know the solutions.
 */
class LevelPlayController extends Controller
{
    public function __construct(private Referee $referee) {}

    public function start(Request $request, string $levelId): Response
    {
        [$player, $level] = $this->resolve($request, $levelId);

        $this->referee->startAttempt($player, $level);

        return response()->noContent();
    }

    public function connect(ConnectAttemptRequest $request, string $levelId): JsonResponse
    {
        [$player, $level] = $this->resolve($request, $levelId, Mode::Connect);

        return response()->json($this->referee->judgeConnection(
            $player,
            $level,
            $request->validated('from'),
            $request->validated('to'),
        ));
    }

    public function guess(GuessAttemptRequest $request, string $levelId): JsonResponse
    {
        [$player, $level] = $this->resolve($request, $levelId, Mode::Guess);

        return response()->json($this->referee->judgeGuess(
            $player,
            $level,
            $request->validated('choice'),
        ));
    }

    public function code(CodeAttemptRequest $request, string $levelId): JsonResponse
    {
        [$player, $level] = $this->resolve($request, $levelId, Mode::Code);

        return response()->json($this->referee->judgeCode(
            $player,
            $level,
            $request->validated('answers'),
        ));
    }

    public function solution(Request $request, string $levelId): JsonResponse
    {
        [$player, $level] = $this->resolve($request, $levelId);

        abort_unless($this->referee->hasCompleted($player, $level), 403, 'Complete the level first.');

        return response()->json($this->referee->solution($level));
    }

    public function hint(Request $request, string $levelId): JsonResponse
    {
        [$player, $level] = $this->resolve($request, $levelId);

        abort_unless($level->hint !== null, 404);

        return response()->json(['hint' => $this->referee->revealHint($player, $level)]);
    }

    /**
     * @return array{Player, LevelDefinition}
     */
    private function resolve(Request $request, string $levelId, ?Mode $mode = null): array
    {
        $level = Levels::find($levelId);

        abort_unless($level !== null, 404);
        abort_if($mode !== null && $level->mode !== $mode, 404);

        $player = Player::query()->find((int) $request->session()->get('player_id'));

        abort_unless($player !== null, 403, 'Join the game first.');
        abort_unless($this->referee->isUnlocked($player, $level), 403, 'This level is still locked.');

        return [$player, $level];
    }
}
