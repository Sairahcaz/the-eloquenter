export interface Point {
    x: number;
    y: number;
}

export type AnchorSide = 'left' | 'right';

export interface Anchor extends Point {
    side: AnchorSide;
}

export interface RenderedConnection {
    id: string;
    path: string;
    state: 'locked' | 'made' | 'rejected';
    delayMs: number;
}

function controlOffset(a: Point, b: Point): number {
    return Math.min(Math.max(Math.abs(b.x - a.x) * 0.5, 40), 160);
}

export function connectionPath(a: Anchor, b: Anchor): string {
    const offset = controlOffset(a, b);
    const c1x = a.x + (a.side === 'left' ? -offset : offset);
    const c2x = b.x + (b.side === 'left' ? -offset : offset);

    return `M ${a.x} ${a.y} C ${c1x} ${a.y}, ${c2x} ${b.y}, ${b.x} ${b.y}`;
}

export function dragPath(from: Anchor, cursor: Point): string {
    const cursorSide: AnchorSide = cursor.x >= from.x ? 'left' : 'right';

    return connectionPath(from, { ...cursor, side: cursorSide });
}
