<?php

namespace App\Game;

/**
 * Structural facts about a relation, read from a real Eloquent relation
 * method via reflection instead of being hand-maintained.
 */
final readonly class ExtractedRelation
{
    /**
     * @param  list<string>  $tables  declaring table first, then pivot/through, then related/target tables
     * @param  list<Connection>  $connections
     * @param  array<string, array<string, 'foreign'|'morph'>>  $columnKeys  badge per table column; primary keys come from schema introspection
     */
    public function __construct(
        public RelationType $type,
        public array $tables,
        public array $connections,
        public array $columnKeys,
        public ?string $pivotTable = null,
    ) {}
}
