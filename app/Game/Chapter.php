<?php

namespace App\Game;

final readonly class Chapter
{
    /**
     * @param  list<LevelDefinition>  $levels
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $subtitle,
        public array $levels,
    ) {}
}
