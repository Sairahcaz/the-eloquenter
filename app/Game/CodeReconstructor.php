<?php

namespace App\Game;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Rebuilds the canonical relation statement from a live relation instance,
 * emitting key arguments only when they deviate from Laravel's conventions.
 * The result feeds the code game mode as fill-in-the-blank parts.
 */
class CodeReconstructor
{
    /** @var array<string, list<string>> */
    private const array METHOD_DECOYS = [
        'hasOne' => ['belongsTo', 'hasMany'],
        'hasMany' => ['belongsTo', 'hasOne'],
        'belongsTo' => ['hasOne', 'hasMany'],
        'belongsToMany' => ['hasMany', 'morphToMany'],
        'hasOneThrough' => ['hasManyThrough', 'hasOne'],
        'hasManyThrough' => ['hasOneThrough', 'hasMany'],
        'morphOne' => ['morphMany', 'hasOne'],
        'morphMany' => ['morphOne', 'hasMany'],
        'morphTo' => ['belongsTo', 'morphOne'],
        'morphToMany' => ['belongsToMany', 'morphMany'],
    ];

    public function __construct(private RelationExtractor $extractor) {}

    /**
     * @param  class-string<Model>  $modelClass
     * @return array{model: string, method: string, codeParts: list<string|array{id: string, options: list<string>, answer: string}>}
     */
    public function reconstruct(string $modelClass, string $method): array
    {
        $model = new $modelClass;
        $relation = $this->extractor->relation($model, $method);

        $methodName = match ($relation::class) {
            HasOne::class => 'hasOne',
            HasMany::class => 'hasMany',
            BelongsTo::class => 'belongsTo',
            BelongsToMany::class => 'belongsToMany',
            HasOneThrough::class => 'hasOneThrough',
            HasManyThrough::class => 'hasManyThrough',
            MorphOne::class => 'morphOne',
            MorphMany::class => 'morphMany',
            MorphTo::class => 'morphTo',
            MorphToMany::class => 'morphToMany',
            default => throw new InvalidArgumentException(
                sprintf('Unsupported relation type [%s] on %s::%s().', $relation::class, $modelClass, $method)
            ),
        };

        $arguments = match ($relation::class) {
            HasOne::class, HasMany::class => $this->hasOneOrManyArguments($model, $relation),
            BelongsTo::class => $this->belongsToArguments($relation),
            BelongsToMany::class => $this->belongsToManyArguments($relation),
            HasOneThrough::class, HasManyThrough::class => $this->throughArguments($model, $relation),
            MorphOne::class, MorphMany::class => $this->morphOneOrManyArguments($relation),
            MorphTo::class => [],
            MorphToMany::class => $this->morphToManyArguments($relation),
        };

        return [
            'model' => class_basename($modelClass),
            'method' => $method,
            'codeParts' => $this->toCodeParts($methodName, $arguments),
        ];
    }

    /**
     * Assemble the statement the answers produce, e.g. for the source-drift
     * guard test: "return $this->hasMany(Order::class, 'customer_ref');".
     *
     * @param  list<string|array{id: string, options: list<string>, answer: string}>  $codeParts
     */
    public static function statement(array $codeParts): string
    {
        return implode('', array_map(
            fn ($part) => is_string($part) ? $part : $part['answer'],
            $codeParts,
        ));
    }

    /**
     * @param  list<array{answer: string, decoys: list<string>}>  $arguments
     * @return list<string|array{id: string, options: list<string>, answer: string}>
     */
    private function toCodeParts(string $methodName, array $arguments): array
    {
        $parts = [
            'return $this->',
            [
                'id' => 'method',
                'options' => [$methodName, ...self::METHOD_DECOYS[$methodName]],
                'answer' => $methodName,
            ],
            '(',
        ];

        foreach ($arguments as $index => $argument) {
            if ($index > 0) {
                $parts[] = ', ';
            }

            $parts[] = [
                'id' => 'arg'.($index + 1),
                'options' => array_values(array_unique([$argument['answer'], ...$argument['decoys']])),
                'answer' => $argument['answer'],
            ];
        }

        $parts[] = ');';

        return $parts;
    }

    /**
     * @return array{answer: string, decoys: list<string>}
     */
    private function classArgument(Model $related, Model $declaring): array
    {
        return [
            'answer' => class_basename($related).'::class',
            'decoys' => [class_basename($declaring).'::class', Str::studly($related->getTable()).'::class'],
        ];
    }

    /**
     * @param  HasOne<*, *>|HasMany<*, *>  $relation
     * @return list<array{answer: string, decoys: list<string>}>
     */
    private function hasOneOrManyArguments(Model $declaring, HasOne|HasMany $relation): array
    {
        $related = $relation->getRelated();
        $arguments = [$this->classArgument($related, $declaring)];

        $conventionalForeignKey = $declaring->getForeignKey();
        $foreignKeyDeviates = $relation->getForeignKeyName() !== $conventionalForeignKey;
        $localKeyDeviates = $relation->getLocalKeyName() !== $declaring->getKeyName();

        if ($foreignKeyDeviates || $localKeyDeviates) {
            $arguments[] = [
                'answer' => "'{$relation->getForeignKeyName()}'",
                'decoys' => ["'{$conventionalForeignKey}'", "'{$related->getForeignKey()}'"],
            ];
        }

        if ($localKeyDeviates) {
            $arguments[] = [
                'answer' => "'{$relation->getLocalKeyName()}'",
                'decoys' => ["'{$declaring->getKeyName()}'", "'{$relation->getForeignKeyName()}'"],
            ];
        }

        return $arguments;
    }

    /**
     * @param  BelongsTo<*, *>  $relation
     * @return list<array{answer: string, decoys: list<string>}>
     */
    private function belongsToArguments(BelongsTo $relation): array
    {
        $child = $relation->getParent();
        $owner = $relation->getRelated();
        $arguments = [$this->classArgument($owner, $child)];

        $conventionalForeignKey = Str::snake($relation->getRelationName()).'_'.$owner->getKeyName();

        if ($relation->getForeignKeyName() !== $conventionalForeignKey) {
            $arguments[] = [
                'answer' => "'{$relation->getForeignKeyName()}'",
                'decoys' => ["'{$conventionalForeignKey}'", "'{$owner->getKeyName()}'"],
            ];
        }

        return $arguments;
    }

    /**
     * @param  BelongsToMany<*, *>  $relation
     * @return list<array{answer: string, decoys: list<string>}>
     */
    private function belongsToManyArguments(BelongsToMany $relation): array
    {
        $parent = $relation->getParent();
        $related = $relation->getRelated();
        $arguments = [$this->classArgument($related, $parent)];

        if ($relation->getTable() !== $parent->joiningTable($related)) {
            $arguments[] = [
                'answer' => "'{$relation->getTable()}'",
                'decoys' => ["'{$parent->joiningTable($related)}'", "'{$related->getTable()}'"],
            ];
        }

        return $arguments;
    }

    /**
     * @param  HasOneThrough<*, *, *>|HasManyThrough<*, *, *>  $relation
     * @return list<array{answer: string, decoys: list<string>}>
     */
    private function throughArguments(Model $declaring, HasOneThrough|HasManyThrough $relation): array
    {
        // Relation::getParent() is the through model for through relations.
        $through = $relation->getParent();
        $far = $relation->getRelated();

        return [
            [
                'answer' => class_basename($far).'::class',
                'decoys' => [class_basename($through).'::class', class_basename($declaring).'::class'],
            ],
            [
                'answer' => class_basename($through).'::class',
                'decoys' => [class_basename($far).'::class', class_basename($declaring).'::class'],
            ],
        ];
    }

    /**
     * @param  MorphOne<*, *>|MorphMany<*, *>  $relation
     * @return list<array{answer: string, decoys: list<string>}>
     */
    private function morphOneOrManyArguments(MorphOne|MorphMany $relation): array
    {
        return [
            $this->classArgument($relation->getRelated(), $relation->getParent()),
            $this->morphNameArgument($relation->getMorphType(), $relation->getRelated()->getTable()),
        ];
    }

    /**
     * @param  MorphToMany<*, *>  $relation
     * @return list<array{answer: string, decoys: list<string>}>
     */
    private function morphToManyArguments(MorphToMany $relation): array
    {
        return [
            $this->classArgument($relation->getRelated(), $relation->getParent()),
            $this->morphNameArgument($relation->getMorphType(), $relation->getTable()),
        ];
    }

    /**
     * @return array{answer: string, decoys: list<string>}
     */
    private function morphNameArgument(string $morphType, string $decoyTable): array
    {
        $name = Str::beforeLast($morphType, '_type');

        return [
            'answer' => "'{$name}'",
            'decoys' => ["'{$morphType}'", "'{$decoyTable}'"],
        ];
    }
}
