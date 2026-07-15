export type RelationType =
    | 'hasOne'
    | 'hasMany'
    | 'belongsTo'
    | 'belongsToMany'
    | 'hasOneThrough'
    | 'hasManyThrough'
    | 'morphOne'
    | 'morphMany'
    | 'morphTo'
    | 'morphToMany';

export type GameMode = 'connect' | 'guess' | 'code';

export type ColumnType = 'id' | 'bigint' | 'string' | 'text' | 'timestamp';

export type ColumnKey = 'primary' | 'foreign' | 'morph';

export interface TableColumn {
    name: string;
    type: ColumnType;
    key?: ColumnKey;
}

export interface TableCardDef {
    id: string;
    name: string;
    columns: TableColumn[];
    pivot: boolean;
    position: { col: 1 | 2 | 3; row: 1 | 2 };
}

export interface ColumnRef {
    table: string;
    column: string;
}

export interface ConnectionDef {
    from: ColumnRef;
    to: ColumnRef;
}

interface LevelBase {
    id: string;
    title: string;
    task: string;
    hasHint: boolean;
    tables: TableCardDef[];
}

export interface ConnectLevel extends LevelBase {
    mode: 'connect';
    relation: RelationType;
    expectedCount: number;
}

export interface GuessLevel extends LevelBase {
    mode: 'guess';
    shownConnections: ConnectionDef[];
    perspective: string;
    choices: RelationType[];
}

export interface CodeBlank {
    id: string;
    options: string[];
}

export interface CodeLevel extends LevelBase {
    mode: 'code';
    shownConnections: ConnectionDef[];
    model: string;
    method: string;
    codeParts: Array<string | CodeBlank>;
}

export type Level = ConnectLevel | GuessLevel | CodeLevel;

export interface Chapter {
    id: number;
    title: string;
    subtitle: string;
    levels: Level[];
}

export interface HighscoreEntry {
    names: string[];
    stars: number;
    seconds: number;
}

export interface Paginated<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export interface PlayerIdentity {
    name: string;
    token: string;
}

export interface ConnectionJudgement {
    correct: boolean;
    solved: boolean;
    stars: number | null;
    made: number;
    relation: RelationType | null;
}

export interface GuessJudgement {
    correct: boolean;
    stars: number | null;
    relation: RelationType | null;
}

export interface CodeJudgement {
    correct: boolean;
    stars: number | null;
    wrongBlanks: string[];
    relation: RelationType | null;
    statement: string | null;
}

export interface LevelResult {
    stars: number;
    relation: RelationType;
    statement: string | null;
}

export interface LevelSolution {
    connections: ConnectionDef[];
    relation: RelationType;
    statement: string | null;
}

/**
 * The connections shown to the player. In connect mode the solution lives
 * on the server only, so there is nothing to draw upfront.
 */
export function levelConnections(level: Level): ConnectionDef[] {
    return level.mode === 'connect' ? [] : level.shownConnections;
}

export function columnRefKey(ref: ColumnRef): string {
    return `${ref.table}.${ref.column}`;
}

export function sameConnection(a: ConnectionDef, b: ConnectionDef): boolean {
    const forward =
        columnRefKey(a.from) === columnRefKey(b.from) &&
        columnRefKey(a.to) === columnRefKey(b.to);
    const reversed =
        columnRefKey(a.from) === columnRefKey(b.to) &&
        columnRefKey(a.to) === columnRefKey(b.from);

    return forward || reversed;
}
