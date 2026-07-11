import type { RelationType } from '@/game/types';

export const relationDescriptions: Record<RelationType, string> = {
    hasOne: 'One related record. The foreign key lives on the related table.',
    hasMany:
        'Many related records. The foreign key lives on the related table.',
    belongsTo:
        'The inverse side: this model holds the foreign key pointing at its owner.',
    belongsToMany:
        'Many on both sides, linked through a pivot table holding both foreign keys.',
    hasOneThrough:
        'One distant record, reached by hopping across an intermediate table.',
    hasManyThrough:
        'Many distant records, reached by hopping across an intermediate table.',
    morphOne:
        'One related record that can belong to different model types (via a _type column).',
    morphMany:
        'Many related records that can belong to different model types (via a _type column).',
    morphTo:
        'The polymorphic inverse: this model belongs to whatever its _type column names.',
    morphToMany:
        'Many-to-many through a polymorphic pivot shared by different model types.',
};

export function relationExample(
    relation: RelationType,
    model: string,
    method: string,
): string {
    return `$${model.toLowerCase()}->${method}`;
}
