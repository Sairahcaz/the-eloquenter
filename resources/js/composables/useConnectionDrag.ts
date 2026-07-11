import { reactive, ref } from 'vue';
import type { Ref } from 'vue';
import type { Point } from '@/game/geometry';
import { columnRefKey } from '@/game/types';
import type { ColumnRef } from '@/game/types';

interface DragOptions {
    isEnabled(): boolean;
    toBoardPoint(clientX: number, clientY: number): Point;
    nearestAnchor(point: Point, radius: number): ColumnRef | null;
    onConnect(from: ColumnRef, to: ColumnRef): void;
}

const SNAP_RADIUS = 28;

export function useConnectionDrag(options: DragOptions): {
    dragFrom: Ref<ColumnRef | null>;
    cursor: Point;
    startDrag(from: ColumnRef, event: PointerEvent): void;
} {
    const dragFrom = ref<ColumnRef | null>(null);
    const cursor = reactive<Point>({ x: 0, y: 0 });

    function dotFromElement(element: Element | null): ColumnRef | null {
        const dot = element?.closest<HTMLElement>('[data-dot]');

        if (!dot?.dataset.table || !dot.dataset.column) {
            return null;
        }

        return { table: dot.dataset.table, column: dot.dataset.column };
    }

    function onPointerMove(event: PointerEvent): void {
        const point = options.toBoardPoint(event.clientX, event.clientY);
        cursor.x = point.x;
        cursor.y = point.y;
    }

    function onPointerUp(event: PointerEvent): void {
        const from = dragFrom.value;
        cleanup();

        if (!from) {
            return;
        }

        // Pointer capture routes pointerup to the origin dot, so the drop
        // target must be resolved from the pointer position instead.
        const hit =
            dotFromElement(
                document.elementFromPoint(event.clientX, event.clientY),
            ) ??
            options.nearestAnchor(
                options.toBoardPoint(event.clientX, event.clientY),
                SNAP_RADIUS,
            );

        if (
            !hit ||
            hit.table === from.table ||
            columnRefKey(hit) === columnRefKey(from)
        ) {
            return;
        }

        options.onConnect(from, hit);
    }

    function cleanup(): void {
        dragFrom.value = null;
        window.removeEventListener('pointermove', onPointerMove);
        window.removeEventListener('pointerup', onPointerUp);
        window.removeEventListener('pointercancel', cleanup);
    }

    function startDrag(from: ColumnRef, event: PointerEvent): void {
        if (!options.isEnabled() || event.button > 0 || dragFrom.value) {
            return;
        }

        event.preventDefault();
        (event.target as Element | null)?.setPointerCapture?.(event.pointerId);

        dragFrom.value = from;
        onPointerMove(event);

        window.addEventListener('pointermove', onPointerMove);
        window.addEventListener('pointerup', onPointerUp);
        window.addEventListener('pointercancel', cleanup);
    }

    return { dragFrom, cursor, startDrag };
}
