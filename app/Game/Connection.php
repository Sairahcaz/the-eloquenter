<?php

namespace App\Game;

/**
 * A single line in the diagram, drawn from a foreign key column to the
 * key column it references.
 */
final readonly class Connection
{
    public function __construct(
        public string $fromTable,
        public string $fromColumn,
        public string $toTable,
        public string $toColumn,
    ) {}

    /**
     * @return array{from: array{table: string, column: string}, to: array{table: string, column: string}}
     */
    public function toArray(): array
    {
        return [
            'from' => ['table' => $this->fromTable, 'column' => $this->fromColumn],
            'to' => ['table' => $this->toTable, 'column' => $this->toColumn],
        ];
    }
}
