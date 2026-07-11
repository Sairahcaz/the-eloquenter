<?php

use App\Game\CodeReconstructor;
use App\Game\LevelPresenter;
use App\Game\Levels;
use App\Game\RelationExtractor;
use App\Game\TablePresenter;

/**
 * @return list<array<string, mixed>>
 */
function presentedChapters(): array
{
    $presenter = new LevelPresenter(
        new RelationExtractor,
        new TablePresenter,
        new CodeReconstructor(new RelationExtractor),
    );

    return $presenter->chapters();
}

/**
 * @return list<array<string, mixed>>
 */
function presentedLevels(): array
{
    return array_merge(...array_column(presentedChapters(), 'levels'));
}

/**
 * Connect-mode solutions never reach the payload, so integrity checks pull
 * them straight from the extractor.
 *
 * @return list<array{from: array{table: string, column: string}, to: array{table: string, column: string}}>
 */
function serverConnections(string $levelId): array
{
    $definition = Levels::find($levelId);

    return array_map(
        fn ($connection) => $connection->toArray(),
        (new RelationExtractor)
            ->extract($definition->model, $definition->method, $definition->morphTargets)
            ->connections,
    );
}

it('presents six chapters with 23 levels and unique stable ids', function () {
    $chapters = presentedChapters();
    $levels = presentedLevels();
    $ids = array_column($levels, 'id');

    expect($chapters)->toHaveCount(6)
        ->and($levels)->toHaveCount(23)
        ->and($ids)->toBe(array_unique($ids));
});

it('references only existing tables and columns in every connection', function () {
    foreach (presentedLevels() as $level) {
        $columnsByTable = collect($level['tables'])
            ->mapWithKeys(fn ($table) => [$table['id'] => array_column($table['columns'], 'name')]);

        $connections = $level['shownConnections'] ?? serverConnections($level['id']);

        expect($connections)->not->toBeEmpty();

        foreach ($connections as $connection) {
            foreach (['from', 'to'] as $side) {
                $ref = $connection[$side];

                expect($columnsByTable)->toHaveKey($ref['table'])
                    ->and($columnsByTable[$ref['table']])->toContain($ref['column']);
            }
        }
    }
});

it('keeps every level inside the board grid with valid column types', function () {
    $allowedTypes = ['id', 'bigint', 'string', 'text', 'timestamp'];

    foreach (presentedLevels() as $level) {
        foreach ($level['tables'] as $table) {
            expect($table['position']['col'])->toBeIn([1, 2, 3])
                ->and($table['position']['row'])->toBeIn([1, 2]);

            foreach ($table['columns'] as $column) {
                expect($column['type'])->toBeIn($allowedTypes);
            }
        }
    }
});

it('ships the mode-specific fields per level', function () {
    foreach (presentedLevels() as $level) {
        match ($level['mode']) {
            'connect' => expect($level['expectedCount'])->toBeGreaterThan(0)
                ->and($level['relation'])->not->toBeEmpty(),
            'guess' => expect($level['choices'])->toHaveCount(4)
                ->and($level['perspective'])->not->toBeEmpty(),
            'code' => expect($level['codeParts'])->not->toBeEmpty()
                ->and($level['model'])->not->toBeEmpty()
                ->and($level['method'])->not->toBeEmpty(),
        };
    }
});

it('never leaks solutions or hints to the client', function () {
    foreach (presentedLevels() as $level) {
        expect($level)->not->toHaveKey('hint')
            ->and($level)->not->toHaveKey('answer')
            ->and($level)->not->toHaveKey('expectedConnections')
            ->and($level['hasHint'])->toBeBool();

        if ($level['mode'] !== 'connect') {
            expect($level)->not->toHaveKey('relation');
        }

        foreach ($level['codeParts'] ?? [] as $part) {
            if (is_array($part)) {
                expect($part)->not->toHaveKey('answer');
            }
        }
    }
});

it('only shows the trimmed users card', function () {
    $level = presentedLevels()[0];
    $users = collect($level['tables'])->firstWhere('id', 'users');

    expect(array_column($users['columns'], 'name'))->toBe(['id', 'name', 'email']);
});
