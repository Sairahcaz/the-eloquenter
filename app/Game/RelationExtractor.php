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
use Illuminate\Database\Eloquent\Relations\Relation;
use InvalidArgumentException;

class RelationExtractor
{
    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<class-string<Model>>  $morphTargets  required for morphTo (targets are not
     *                                                   reflectable); optional extra owners for morphToMany
     */
    public function extract(string $modelClass, string $method, array $morphTargets = []): ExtractedRelation
    {
        $model = new $modelClass;
        $relation = $this->relation($model, $method);

        return match ($relation::class) {
            HasOne::class, HasMany::class => $this->extractHasOneOrMany($relation),
            BelongsTo::class => $this->extractBelongsTo($relation),
            BelongsToMany::class => $this->extractBelongsToMany($relation),
            HasOneThrough::class, HasManyThrough::class => $this->extractThrough($model, $relation),
            MorphOne::class, MorphMany::class => $this->extractMorphOneOrMany($relation),
            MorphTo::class => $this->extractMorphTo($relation, $morphTargets),
            MorphToMany::class => $this->extractMorphToMany($relation, $morphTargets),
            default => throw new InvalidArgumentException(
                sprintf('Unsupported relation type [%s] on %s::%s().', $relation::class, $modelClass, $method)
            ),
        };
    }

    /**
     * @return Relation<*, *, *>
     */
    public function relation(Model $model, string $method): Relation
    {
        $relation = $model->{$method}();

        if (! $relation instanceof Relation) {
            throw new InvalidArgumentException(
                sprintf('%s::%s() does not return an Eloquent relation.', $model::class, $method)
            );
        }

        return $relation;
    }

    /**
     * @param  HasOne<*, *>|HasMany<*, *>  $relation
     */
    private function extractHasOneOrMany(HasOne|HasMany $relation): ExtractedRelation
    {
        $parentTable = $relation->getParent()->getTable();
        $childTable = $relation->getRelated()->getTable();
        $foreignKey = $relation->getForeignKeyName();

        return new ExtractedRelation(
            type: $relation instanceof HasOne ? RelationType::HasOne : RelationType::HasMany,
            tables: [$parentTable, $childTable],
            connections: [new Connection($childTable, $foreignKey, $parentTable, $relation->getLocalKeyName())],
            columnKeys: [$childTable => [$foreignKey => 'foreign']],
        );
    }

    /**
     * @param  BelongsTo<*, *>  $relation
     */
    private function extractBelongsTo(BelongsTo $relation): ExtractedRelation
    {
        $childTable = $relation->getParent()->getTable();
        $ownerTable = $relation->getRelated()->getTable();
        $foreignKey = $relation->getForeignKeyName();

        return new ExtractedRelation(
            type: RelationType::BelongsTo,
            tables: [$childTable, $ownerTable],
            connections: [new Connection($childTable, $foreignKey, $ownerTable, $relation->getOwnerKeyName())],
            columnKeys: [$childTable => [$foreignKey => 'foreign']],
        );
    }

    /**
     * @param  BelongsToMany<*, *>  $relation
     */
    private function extractBelongsToMany(BelongsToMany $relation): ExtractedRelation
    {
        $parentTable = $relation->getParent()->getTable();
        $relatedTable = $relation->getRelated()->getTable();
        $pivotTable = $relation->getTable();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();

        return new ExtractedRelation(
            type: RelationType::BelongsToMany,
            tables: [$parentTable, $pivotTable, $relatedTable],
            connections: [
                new Connection($pivotTable, $foreignPivotKey, $parentTable, $relation->getParentKeyName()),
                new Connection($pivotTable, $relatedPivotKey, $relatedTable, $relation->getRelatedKeyName()),
            ],
            columnKeys: [$pivotTable => [$foreignPivotKey => 'foreign', $relatedPivotKey => 'foreign']],
            pivotTable: $pivotTable,
        );
    }

    /**
     * @param  HasOneThrough<*, *, *>|HasManyThrough<*, *, *>  $relation
     */
    private function extractThrough(Model $declaring, HasOneThrough|HasManyThrough $relation): ExtractedRelation
    {
        // There is no accessor for the through model, but the relation's
        // constructor passes it to Relation::__construct() as the parent.
        $throughTable = $relation->getParent()->getTable();
        $declaringTable = $declaring->getTable();
        $farTable = $relation->getRelated()->getTable();
        $firstKey = $relation->getFirstKeyName();
        $secondKey = $relation->getForeignKeyName();

        return new ExtractedRelation(
            type: $relation instanceof HasOneThrough ? RelationType::HasOneThrough : RelationType::HasManyThrough,
            tables: [$declaringTable, $throughTable, $farTable],
            connections: [
                new Connection($throughTable, $firstKey, $declaringTable, $relation->getLocalKeyName()),
                new Connection($farTable, $secondKey, $throughTable, $relation->getSecondLocalKeyName()),
            ],
            columnKeys: [
                $throughTable => [$firstKey => 'foreign'],
                $farTable => [$secondKey => 'foreign'],
            ],
        );
    }

    /**
     * @param  MorphOne<*, *>|MorphMany<*, *>  $relation
     */
    private function extractMorphOneOrMany(MorphOne|MorphMany $relation): ExtractedRelation
    {
        $parentTable = $relation->getParent()->getTable();
        $childTable = $relation->getRelated()->getTable();
        $foreignKey = $relation->getForeignKeyName();

        return new ExtractedRelation(
            type: $relation instanceof MorphOne ? RelationType::MorphOne : RelationType::MorphMany,
            tables: [$parentTable, $childTable],
            connections: [new Connection($childTable, $foreignKey, $parentTable, $relation->getLocalKeyName())],
            columnKeys: [$childTable => [$foreignKey => 'foreign', $relation->getMorphType() => 'morph']],
        );
    }

    /**
     * @param  MorphTo<*, *>  $relation
     * @param  list<class-string<Model>>  $morphTargets
     */
    private function extractMorphTo(MorphTo $relation, array $morphTargets): ExtractedRelation
    {
        if ($morphTargets === []) {
            throw new InvalidArgumentException('morphTo relations need explicit morph targets; they cannot be reflected.');
        }

        $childTable = $relation->getParent()->getTable();
        $foreignKey = $relation->getForeignKeyName();
        $targetTables = [];
        $connections = [];

        foreach ($morphTargets as $target) {
            $targetModel = new $target;
            $targetTables[] = $targetModel->getTable();
            $connections[] = new Connection($childTable, $foreignKey, $targetModel->getTable(), $targetModel->getKeyName());
        }

        return new ExtractedRelation(
            type: RelationType::MorphTo,
            tables: [$childTable, ...$targetTables],
            connections: $connections,
            columnKeys: [$childTable => [$foreignKey => 'foreign', $relation->getMorphType() => 'morph']],
        );
    }

    /**
     * @param  MorphToMany<*, *>  $relation
     * @param  list<class-string<Model>>  $morphTargets
     */
    private function extractMorphToMany(MorphToMany $relation, array $morphTargets): ExtractedRelation
    {
        $parentTable = $relation->getParent()->getTable();
        $relatedTable = $relation->getRelated()->getTable();
        $pivotTable = $relation->getTable();
        $foreignPivotKey = $relation->getForeignPivotKeyName();
        $relatedPivotKey = $relation->getRelatedPivotKeyName();

        $ownerTables = [$parentTable];
        $connections = [new Connection($pivotTable, $foreignPivotKey, $parentTable, $relation->getParentKeyName())];

        foreach ($morphTargets as $target) {
            $targetModel = new $target;

            if ($targetModel->getTable() === $parentTable) {
                continue;
            }

            $ownerTables[] = $targetModel->getTable();
            $connections[] = new Connection($pivotTable, $foreignPivotKey, $targetModel->getTable(), $targetModel->getKeyName());
        }

        $connections[] = new Connection($pivotTable, $relatedPivotKey, $relatedTable, $relation->getRelatedKeyName());

        return new ExtractedRelation(
            type: RelationType::MorphToMany,
            tables: [...$ownerTables, $pivotTable, $relatedTable],
            connections: $connections,
            columnKeys: [
                $pivotTable => [
                    $foreignPivotKey => 'foreign',
                    $relation->getMorphType() => 'morph',
                    $relatedPivotKey => 'foreign',
                ],
            ],
            pivotTable: $pivotTable,
        );
    }
}
