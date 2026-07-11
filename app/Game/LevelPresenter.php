<?php

namespace App\Game;

/**
 * Combines a curated LevelDefinition with the facts extracted from the real
 * model into the payload shape the Vue game expects.
 */
class LevelPresenter
{
    public function __construct(
        private RelationExtractor $extractor,
        private TablePresenter $tablePresenter,
        private CodeReconstructor $codeReconstructor,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function chapters(): array
    {
        return array_map(fn (Chapter $chapter) => [
            'id' => $chapter->id,
            'title' => $chapter->title,
            'subtitle' => $chapter->subtitle,
            'levels' => array_map($this->present(...), $chapter->levels),
        ], Levels::chapters());
    }

    /**
     * @return array<string, mixed>
     */
    public function present(LevelDefinition $definition): array
    {
        $extraction = $this->extractor->extract($definition->model, $definition->method, $definition->morphTargets);
        $connections = array_map(fn (Connection $connection) => $connection->toArray(), $extraction->connections);

        $level = [
            'id' => $definition->id,
            'title' => $definition->title,
            'task' => $definition->task,
            'mode' => $definition->mode->value,
            'hasHint' => $definition->hint !== null,
            'tables' => $this->tables($definition, $extraction),
        ];

        return $level + match ($definition->mode) {
            // The relation name is teaching material in connect mode, but it
            // would spoil the answer in guess and code mode.
            Mode::Connect => [
                'relation' => $extraction->type->value,
                'expectedCount' => count($connections),
            ],
            Mode::Guess => [
                'shownConnections' => $connections,
                'perspective' => $definition->perspective,
                'choices' => array_map(fn (RelationType $type) => $type->value, $definition->guessChoices ?? []),
            ],
            Mode::Code => [
                'shownConnections' => $connections,
            ] + $this->withoutCodeAnswers($this->codeReconstructor->reconstruct($definition->model, $definition->method)),
        };
    }

    /**
     * The answers stay on the server; the client only sees the options.
     *
     * @param  array{model: string, method: string, codeParts: list<string|array{id: string, options: list<string>, answer: string}>}  $reconstruction
     * @return array{model: string, method: string, codeParts: list<string|array{id: string, options: list<string>}>}
     */
    private function withoutCodeAnswers(array $reconstruction): array
    {
        $reconstruction['codeParts'] = array_map(
            fn (string|array $part) => is_array($part) ? ['id' => $part['id'], 'options' => $part['options']] : $part,
            $reconstruction['codeParts'],
        );

        return $reconstruction;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function tables(LevelDefinition $definition, ExtractedRelation $extraction): array
    {
        $defaultLayout = $this->defaultLayout($extraction->tables);

        return array_map(function (string $table) use ($definition, $extraction, $defaultLayout) {
            [$col, $row] = $definition->layout[$table] ?? $defaultLayout[$table];

            return $this->tablePresenter->present(
                $table,
                $extraction->columnKeys[$table] ?? [],
                $table === $extraction->pivotTable,
            ) + ['position' => ['col' => $col, 'row' => $row]];
        }, $extraction->tables);
    }

    /**
     * @param  list<string>  $tables
     * @return array<string, array{int, int}>
     */
    private function defaultLayout(array $tables): array
    {
        $slots = match (count($tables)) {
            2 => [[1, 1], [3, 1]],
            3 => [[1, 1], [2, 1], [3, 1]],
            default => [[1, 1], [1, 2], [2, 1], [3, 1]],
        };

        return array_combine($tables, array_slice($slots, 0, count($tables)));
    }
}
