<?php

namespace App\Game;

use Illuminate\Database\Eloquent\Model;

/**
 * The curated part of a level: which relation it teaches, in which mode, and
 * the copy around it. Everything structural (tables, columns, connections,
 * code) is extracted from the real model at presentation time.
 */
final readonly class LevelDefinition
{
    /**
     * @param  class-string<Model>  $model
     * @param  list<class-string<Model>>  $morphTargets
     * @param  list<RelationType>|null  $guessChoices
     * @param  array<string, array{int, int}>  $layout  table => [col, row], overrides the default grid
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $task,
        public Mode $mode,
        public string $model,
        public string $method,
        public array $morphTargets = [],
        public ?string $hint = null,
        public ?string $perspective = null,
        public ?array $guessChoices = null,
        public array $layout = [],
    ) {}
}
