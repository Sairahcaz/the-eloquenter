<?php

use App\Game\CodeReconstructor;
use App\Game\LevelPresenter;
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

        $connections = $level['expectedConnections'] ?? $level['shownConnections'];

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
            'connect' => expect($level)->toHaveKey('expectedConnections'),
            'guess' => expect($level['choices'])->toHaveCount(4)
                ->and($level['choices'])->toContain($level['answer'])
                ->and($level['perspective'])->not->toBeEmpty(),
            'code' => expect($level['codeParts'])->not->toBeEmpty()
                ->and($level['model'])->not->toBeEmpty()
                ->and($level['method'])->not->toBeEmpty(),
        };
    }
});

it('only shows the trimmed users card', function () {
    $level = presentedLevels()[0];
    $users = collect($level['tables'])->firstWhere('id', 'users');

    expect(array_column($users['columns'], 'name'))->toBe(['id', 'name', 'email']);
});
