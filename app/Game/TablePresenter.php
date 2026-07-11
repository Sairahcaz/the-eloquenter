<?php

namespace App\Game;

use Illuminate\Support\Facades\Schema;

class TablePresenter
{
    /**
     * Starter columns on the users table that would clutter its card.
     *
     * @var list<string>
     */
    private const array HIDDEN_COLUMNS = ['email_verified_at', 'password', 'remember_token', 'created_at', 'updated_at'];

    /** @var array<string, list<array{name: string, type_name: string, auto_increment: bool}>> */
    private array $schemaColumns = [];

    /**
     * @param  array<string, 'foreign'|'morph'>  $columnKeys
     * @return array{id: string, name: string, columns: list<array{name: string, type: string, key?: string}>, pivot: bool}
     */
    public function present(string $table, array $columnKeys, bool $pivot): array
    {
        $columns = [];

        foreach ($this->columnsFor($table) as $column) {
            if (in_array($column['name'], self::HIDDEN_COLUMNS, true)) {
                continue;
            }

            $presented = [
                'name' => $column['name'],
                'type' => $this->mapType($column),
            ];

            if ($key = $this->keyFor($column, $columnKeys)) {
                $presented['key'] = $key;
            }

            $columns[] = $presented;
        }

        return [
            'id' => $table,
            'name' => $table,
            'columns' => $columns,
            'pivot' => $pivot,
        ];
    }

    /**
     * @return list<array{name: string, type_name: string, auto_increment: bool}>
     */
    private function columnsFor(string $table): array
    {
        return $this->schemaColumns[$table] ??= array_map(
            fn (array $column): array => [
                'name' => (string) $column['name'],
                'type_name' => (string) $column['type_name'],
                'auto_increment' => (bool) $column['auto_increment'],
            ],
            array_values(Schema::getColumns($table)),
        );
    }

    /**
     * The type_name values are SQLite-specific; the level payload test pins them.
     *
     * @param  array{type_name: string, auto_increment: bool}  $column
     */
    private function mapType(array $column): string
    {
        return match ($column['type_name']) {
            'integer' => $column['auto_increment'] ? 'id' : 'bigint',
            'varchar' => 'string',
            'text' => 'text',
            'datetime' => 'timestamp',
            default => 'string',
        };
    }

    /**
     * @param  array{name: string, auto_increment: bool}  $column
     * @param  array<string, 'foreign'|'morph'>  $columnKeys
     */
    private function keyFor(array $column, array $columnKeys): ?string
    {
        if ($column['auto_increment']) {
            return 'primary';
        }

        return $columnKeys[$column['name']] ?? null;
    }
}
