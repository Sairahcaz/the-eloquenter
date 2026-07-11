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
    relation: RelationType;
    hint: string | null;
    tables: TableCardDef[];
}

export interface ConnectLevel extends LevelBase {
    mode: 'connect';
    expectedConnections: ConnectionDef[];
}

export interface GuessLevel extends LevelBase {
    mode: 'guess';
    shownConnections: ConnectionDef[];
    perspective: string;
    choices: RelationType[];
    answer: RelationType;
}

export interface CodeBlank {
    id: string;
    options: string[];
    answer: string;
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
    name: string;
    stars: number;
}

export function levelConnections(level: Level): ConnectionDef[] {
    return level.mode === 'connect'
        ? level.expectedConnections
        : level.shownConnections;
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
