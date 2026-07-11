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
            'relation' => $extraction->type->value,
            'mode' => $definition->mode->value,
            'hint' => $definition->hint,
            'tables' => $this->tables($definition, $extraction),
        ];

        return $level + match ($definition->mode) {
            Mode::Connect => [
                'expectedConnections' => $connections,
            ],
            Mode::Guess => [
                'shownConnections' => $connections,
                'perspective' => $definition->perspective,
                'choices' => array_map(fn (RelationType $type) => $type->value, $definition->guessChoices ?? []),
                'answer' => $extraction->type->value,
            ],
            Mode::Code => [
                'shownConnections' => $connections,
            ] + $this->codeReconstructor->reconstruct($definition->model, $definition->method),
        };
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
