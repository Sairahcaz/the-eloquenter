<?php

namespace App\Game;

use App\Models\LevelAttempt;
use App\Models\Player;
use Illuminate\Support\Arr;

/**
 * The server-side authority over gameplay. It owns the running attempt,
 * judges every move against the truth extracted from the real models, and
 * records completions with the resulting star rating. Solutions never
 * leave the backend.
 */
class Referee
{
    public function __construct(
        private RelationExtractor $extractor,
        private CodeReconstructor $codeReconstructor,
    ) {}

    public function isUnlocked(Player $player, LevelDefinition $level): bool
    {
        $previous = Levels::previous($level->id);

        return $previous === null || $this->hasCompleted($player, $previous);
    }

    public function hasCompleted(Player $player, LevelDefinition $level): bool
    {
        return $player->completions()->where('level_id', $level->id)->exists();
    }

    /**
     * The full solution for reviewing a level the player already beat.
     *
     * @return array{connections: list<array{from: array{table: string, column: string}, to: array{table: string, column: string}}>, relation: string, statement: string|null}
     */
    public function solution(LevelDefinition $level): array
    {
        return [
            'connections' => $this->expectedConnections($level),
            'relation' => $this->extraction($level)->type->value,
            'statement' => $level->mode === Mode::Code ? $this->codeStatement($level) : null,
        ];
    }

    public function startAttempt(Player $player, LevelDefinition $level): void
    {
        $player->attempts()->updateOrCreate(
            ['level_id' => $level->id],
            ['mistakes' => 0, 'hint_used' => false, 'made_connections' => [], 'started_at' => now()],
        );
    }

    /**
     * @param  array{table: string, column: string}  $from
     * @param  array{table: string, column: string}  $to
     * @return array{correct: bool, solved: bool, stars: int|null, made: int, relation: string|null}
     */
    public function judgeConnection(Player $player, LevelDefinition $level, array $from, array $to): array
    {
        $attempt = $this->attempt($player, $level);
        $expected = $this->expectedConnections($level);

        $match = Arr::first(
            $expected,
            fn (array $connection) => $this->sameConnection($connection, ['from' => $from, 'to' => $to]),
        );

        if ($match === null) {
            $attempt->increment('mistakes');

            return [
                'correct' => false,
                'solved' => false,
                'stars' => null,
                'made' => count($attempt->made_connections),
                'relation' => null,
            ];
        }

        $made = $attempt->made_connections;

        if (! Arr::first($made, fn (array $connection) => $this->sameConnection($connection, $match))) {
            $made[] = $match;
            $attempt->made_connections = $made;
            $attempt->save();
        }

        $solved = count($made) === count($expected);

        return [
            'correct' => true,
            'solved' => $solved,
            'stars' => $solved ? $this->complete($player, $level, $attempt) : null,
            'made' => count($made),
            'relation' => $solved ? $this->extraction($level)->type->value : null,
        ];
    }

    /**
     * @return array{correct: bool, stars: int|null, relation: string|null}
     */
    public function judgeGuess(Player $player, LevelDefinition $level, string $choice): array
    {
        $attempt = $this->attempt($player, $level);
        $answer = $this->extraction($level)->type;

        if ($choice !== $answer->value) {
            $attempt->increment('mistakes');

            return ['correct' => false, 'stars' => null, 'relation' => null];
        }

        return [
            'correct' => true,
            'stars' => $this->complete($player, $level, $attempt),
            'relation' => $answer->value,
        ];
    }

    /**
     * @param  array<string, string>  $answers
     * @return array{correct: bool, stars: int|null, wrongBlanks: list<string>, relation: string|null, statement: string|null}
     */
    public function judgeCode(Player $player, LevelDefinition $level, array $answers): array
    {
        $attempt = $this->attempt($player, $level);

        $wrongBlanks = [];

        foreach ($this->codeAnswers($level) as $blankId => $answer) {
            if (($answers[$blankId] ?? null) !== $answer) {
                $wrongBlanks[] = $blankId;
            }
        }

        if ($wrongBlanks !== []) {
            $attempt->increment('mistakes');

            return ['correct' => false, 'stars' => null, 'wrongBlanks' => $wrongBlanks, 'relation' => null, 'statement' => null];
        }

        return [
            'correct' => true,
            'stars' => $this->complete($player, $level, $attempt),
            'wrongBlanks' => [],
            'relation' => $this->extraction($level)->type->value,
            'statement' => $this->codeStatement($level),
        ];
    }

    public function revealHint(Player $player, LevelDefinition $level): ?string
    {
        $this->attempt($player, $level)->update(['hint_used' => true]);

        return $level->hint;
    }

    /**
     * Records the run, keeping the best one per level: more stars wins,
     * equal stars keep the faster time.
     */
    private function complete(Player $player, LevelDefinition $level, LevelAttempt $attempt): int
    {
        $stars = max(1, 3 - $attempt->mistakes);

        if ($attempt->hint_used) {
            $stars = min($stars, 2);
        }

        $startedAt = $attempt->started_at ?? $attempt->updated_at ?? now();
        $seconds = max(0, (int) $startedAt->diffInSeconds(now()));

        $completion = $player->completions()->firstOrNew(['level_id' => $level->id]);

        if ($stars > ($completion->stars ?? 0)) {
            $completion->stars = $stars;
            $completion->duration_seconds = $seconds;
        } elseif ($stars === $completion->stars && $seconds < ($completion->duration_seconds ?? PHP_INT_MAX)) {
            $completion->duration_seconds = $seconds;
        }

        $completion->save();

        return $stars;
    }

    private function attempt(Player $player, LevelDefinition $level): LevelAttempt
    {
        return $player->attempts()->firstOrCreate(
            ['level_id' => $level->id],
            ['mistakes' => 0, 'hint_used' => false, 'made_connections' => [], 'started_at' => now()],
        );
    }

    private function extraction(LevelDefinition $level): ExtractedRelation
    {
        return $this->extractor->extract($level->model, $level->method, $level->morphTargets);
    }

    /**
     * @return list<array{from: array{table: string, column: string}, to: array{table: string, column: string}}>
     */
    private function expectedConnections(LevelDefinition $level): array
    {
        return array_map(
            fn (Connection $connection) => $connection->toArray(),
            $this->extraction($level)->connections,
        );
    }

    /**
     * The completed relation statement, revealed only after solving.
     */
    private function codeStatement(LevelDefinition $level): string
    {
        $parts = $this->codeReconstructor->reconstruct($level->model, $level->method)['codeParts'];

        return implode('', array_map(
            fn (string|array $part) => is_array($part) ? $part['answer'] : $part,
            $parts,
        ));
    }

    /**
     * @return array<string, string>
     */
    private function codeAnswers(LevelDefinition $level): array
    {
        $parts = $this->codeReconstructor->reconstruct($level->model, $level->method)['codeParts'];
        $answers = [];

        foreach ($parts as $part) {
            if (is_array($part)) {
                $answers[$part['id']] = $part['answer'];
            }
        }

        return $answers;
    }

    /**
     * @param  array{from: array{table: string, column: string}, to: array{table: string, column: string}}  $a
     * @param  array{from: array{table: string, column: string}, to: array{table: string, column: string}}  $b
     */
    private function sameConnection(array $a, array $b): bool
    {
        $key = fn (array $ref) => "{$ref['table']}.{$ref['column']}";

        return ($key($a['from']) === $key($b['from']) && $key($a['to']) === $key($b['to']))
            || ($key($a['from']) === $key($b['to']) && $key($a['to']) === $key($b['from']));
    }
}
