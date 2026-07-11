<?php

namespace App\Game;

use App\Models\Customer;
use App\Models\Mechanic;
use App\Models\Phone;
use App\Models\Post;
use App\Models\Project;
use App\Models\Reaction;
use App\Models\User;
use App\Models\Video;

/**
 * The curriculum. Level ids are persisted in players' browsers as progress
 * keys: never renumber existing ids when inserting or reordering levels.
 */
class Levels
{
    /**
     * @return list<Chapter>
     */
    public static function chapters(): array
    {
        return [
            new Chapter(1, 'One to One', 'hasOne & belongsTo', self::chapterOne()),
            new Chapter(2, 'One to Many', 'hasMany & foreign keys', self::chapterTwo()),
            new Chapter(3, 'Many to Many', 'belongsToMany & pivot tables', self::chapterThree()),
            new Chapter(4, 'Has Through', 'hasOneThrough & hasManyThrough', self::chapterFour()),
            new Chapter(5, 'Polymorphic', 'morphOne, morphMany & morphTo', self::chapterFive()),
            new Chapter(6, 'Poly Many to Many', 'morphToMany', self::chapterSix()),
        ];
    }

    /**
     * @return list<LevelDefinition>
     */
    public static function all(): array
    {
        return array_merge(...array_map(fn (Chapter $chapter) => $chapter->levels, self::chapters()));
    }

    /**
     * @return list<LevelDefinition>
     */
    private static function chapterOne(): array
    {
        return [
            new LevelDefinition(
                id: 'c1-l1',
                title: 'First Connection',
                task: 'A user has one phone. Connect the tables so Eloquent can find it.',
                mode: Mode::Connect,
                model: User::class,
                method: 'phone',
                hint: 'The foreign key lives on the phones table and points at the users primary key.',
            ),
            new LevelDefinition(
                id: 'c1-l2',
                title: 'Name That Relation',
                task: 'Every user carries a single phone.',
                mode: Mode::Guess,
                model: User::class,
                method: 'phone',
                perspective: 'From the User model, which relation returns the phone?',
                guessChoices: [RelationType::HasOne, RelationType::BelongsTo, RelationType::HasMany, RelationType::MorphOne],
                hint: 'The User model owns the phone, and there is exactly one.',
            ),
            new LevelDefinition(
                id: 'c1-l3',
                title: 'Write It in Eloquent',
                task: 'Complete the phone() method on the User model.',
                mode: Mode::Code,
                model: User::class,
                method: 'phone',
                hint: 'The convention-following foreign key needs no extra arguments.',
            ),
            new LevelDefinition(
                id: 'c1-l4',
                title: 'The Other Side',
                task: 'Same tables, opposite direction: this time you start from the phone.',
                mode: Mode::Guess,
                model: Phone::class,
                method: 'user',
                perspective: 'From the Phone model, which relation returns the user?',
                guessChoices: [RelationType::BelongsTo, RelationType::HasOne, RelationType::HasMany, RelationType::BelongsToMany],
                hint: 'The model holding the foreign key is the one that belongs to the other.',
            ),
            new LevelDefinition(
                id: 'c1-l5',
                title: 'Inverse in Code',
                task: 'Complete the user() method on the Phone model.',
                mode: Mode::Code,
                model: Phone::class,
                method: 'user',
                hint: 'belongsTo points from the foreign key holder back to the owner.',
            ),
        ];
    }

    /**
     * @return list<LevelDefinition>
     */
    private static function chapterTwo(): array
    {
        return [
            new LevelDefinition(
                id: 'c2-l1',
                title: 'One to Many',
                task: 'A user has many posts. Wire it up.',
                mode: Mode::Connect,
                model: User::class,
                method: 'posts',
                hint: 'Exactly like hasOne: the "many" only changes what Eloquent returns.',
            ),
            new LevelDefinition(
                id: 'c2-l2',
                title: 'Posts and Comments',
                task: 'Readers keep leaving comments under every post.',
                mode: Mode::Guess,
                model: Post::class,
                method: 'comments',
                perspective: 'From the Post model, which relation returns the comments?',
                guessChoices: [RelationType::HasMany, RelationType::HasOne, RelationType::BelongsTo, RelationType::BelongsToMany],
                hint: 'One post, many comments, and the foreign key sits on comments.',
            ),
            new LevelDefinition(
                id: 'c2-l3',
                title: 'Comments in Code',
                task: 'Complete the comments() method on the Post model.',
                mode: Mode::Code,
                model: Post::class,
                method: 'comments',
                hint: 'Conventions hold here, so one argument is enough.',
            ),
            new LevelDefinition(
                id: 'c2-l4',
                title: 'Rebel Schema',
                task: 'A customer has many orders, but this schema ignores naming conventions.',
                mode: Mode::Connect,
                model: Customer::class,
                method: 'orders',
                hint: 'There is no customer_id here. Look for the column that still points at customers.',
            ),
            new LevelDefinition(
                id: 'c2-l5',
                title: 'Convention Breaker',
                task: 'Complete the orders() method. Eloquent needs to know about the unusual foreign key.',
                mode: Mode::Code,
                model: Customer::class,
                method: 'orders',
                hint: 'When the foreign key breaks convention, pass it as the second argument.',
            ),
        ];
    }

    /**
     * @return list<LevelDefinition>
     */
    private static function chapterThree(): array
    {
        return [
            new LevelDefinition(
                id: 'c3-l1',
                title: 'Meet the Pivot',
                task: 'Users belong to many roles. Two connections are needed: the pivot table links both sides.',
                mode: Mode::Connect,
                model: User::class,
                method: 'roles',
                hint: 'Neither users nor roles points at the other. The pivot table holds both foreign keys.',
            ),
            new LevelDefinition(
                id: 'c3-l2',
                title: 'Name the Pivot Relation',
                task: 'Users and roles are linked through the role_user table.',
                mode: Mode::Guess,
                model: User::class,
                method: 'roles',
                perspective: 'From the User model, which relation returns the roles?',
                guessChoices: [RelationType::BelongsToMany, RelationType::HasMany, RelationType::MorphToMany, RelationType::HasManyThrough],
                hint: 'A pivot table between two real tables means "belongs to many".',
            ),
            new LevelDefinition(
                id: 'c3-l3',
                title: 'Pivot in Code',
                task: 'Complete the roles() method on the User model.',
                mode: Mode::Code,
                model: User::class,
                method: 'roles',
                hint: 'The pivot table follows convention (role_user), so Eloquent finds it by itself.',
            ),
        ];
    }

    /**
     * @return list<LevelDefinition>
     */
    private static function chapterFour(): array
    {
        return [
            new LevelDefinition(
                id: 'c4-l1',
                title: 'Through the Car',
                task: 'A mechanic can reach the car owner through the car. Chain two connections.',
                mode: Mode::Connect,
                model: Mechanic::class,
                method: 'carOwner',
                hint: 'cars points at mechanics, owners points at cars. Two hops.',
            ),
            new LevelDefinition(
                id: 'c4-l2',
                title: 'Through in Code',
                task: 'Complete the carOwner() method. Mind the argument order.',
                mode: Mode::Code,
                model: Mechanic::class,
                method: 'carOwner',
                hint: 'First the model you want to reach, then the model you travel through.',
            ),
            new LevelDefinition(
                id: 'c4-l3',
                title: 'Deployment Pipeline',
                task: 'A project has many deployments through its environments.',
                mode: Mode::Connect,
                model: Project::class,
                method: 'deployments',
                hint: 'Same pattern as before, just with many results at the end.',
            ),
            new LevelDefinition(
                id: 'c4-l4',
                title: 'Pipeline in Code',
                task: 'Complete the deployments() method on the Project model.',
                mode: Mode::Code,
                model: Project::class,
                method: 'deployments',
                hint: 'Deployment is the destination, Environment is the stepping stone.',
            ),
        ];
    }

    /**
     * @return list<LevelDefinition>
     */
    private static function chapterFive(): array
    {
        return [
            new LevelDefinition(
                id: 'c5-l1',
                title: 'Shape Shifter',
                task: 'A user has one image, but images can belong to any model. That is what imageable_type is for.',
                mode: Mode::Connect,
                model: User::class,
                method: 'image',
                hint: 'Connect imageable_id like a normal foreign key. The _type column stores the owning model.',
            ),
            new LevelDefinition(
                id: 'c5-l2',
                title: 'Reactions Everywhere',
                task: 'Videos collect reactions, and posts do too. One reactions table serves them both.',
                mode: Mode::Guess,
                model: Video::class,
                method: 'reactions',
                perspective: 'From the Video model, which relation returns the reactions?',
                guessChoices: [RelationType::MorphMany, RelationType::HasMany, RelationType::MorphOne, RelationType::MorphToMany],
                hint: 'Many results plus a _type column means morph.',
            ),
            new LevelDefinition(
                id: 'c5-l3',
                title: 'Morph in Code',
                task: 'Complete the reactions() method on the Video model.',
                mode: Mode::Code,
                model: Video::class,
                method: 'reactions',
                hint: 'Morph relations always take the morph name as second argument.',
            ),
            new LevelDefinition(
                id: 'c5-l4',
                title: 'Who Do I Belong To?',
                task: 'A reaction sticks to whatever it reacts to: a post or a video.',
                mode: Mode::Guess,
                model: Reaction::class,
                method: 'reactable',
                morphTargets: [Post::class, Video::class],
                perspective: 'From the Reaction model, which relation returns the reacted-to model?',
                guessChoices: [RelationType::MorphTo, RelationType::BelongsTo, RelationType::MorphOne, RelationType::MorphMany],
                layout: ['reactions' => [1, 1], 'posts' => [3, 1], 'videos' => [3, 2]],
                hint: 'It is the polymorphic cousin of belongsTo.',
            ),
        ];
    }

    /**
     * @return list<LevelDefinition>
     */
    private static function chapterSix(): array
    {
        return [
            new LevelDefinition(
                id: 'c6-l1',
                title: 'The Grand Finale',
                task: 'Posts and videos share tags through one polymorphic pivot. Three connections to rule them all.',
                mode: Mode::Connect,
                model: Post::class,
                method: 'tags',
                morphTargets: [Post::class, Video::class],
                layout: ['posts' => [1, 1], 'videos' => [1, 2], 'taggables' => [2, 1], 'tags' => [3, 1]],
                hint: 'taggable_id points at posts AND videos; tag_id points at tags.',
            ),
            new LevelDefinition(
                id: 'c6-l2',
                title: 'Master of Morphs',
                task: 'Complete the tags() method on the Post model.',
                mode: Mode::Code,
                model: Post::class,
                method: 'tags',
                hint: 'Like belongsToMany, but polymorphic, so the morph name rides along.',
            ),
        ];
    }
}
