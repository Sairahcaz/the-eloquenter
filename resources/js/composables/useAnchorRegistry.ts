import { onBeforeUnmount, onMounted, reactive } from 'vue';
import type { Ref } from 'vue';
import type { Anchor, AnchorSide, Point } from '@/game/geometry';
import { columnRefKey } from '@/game/types';
import type { ColumnRef } from '@/game/types';

/**
 * Tracks the dot elements on the board and turns them into board-relative
 * coordinates. Re-measures on resize and after webfonts load, since both
 * shift the card layout after the initial mount.
 */
export function useAnchorRegistry(
    boardEl: Ref<HTMLElement | null>,
    sideFor: (ref: ColumnRef) => AnchorSide,
) {
    const dots = new Map<string, { ref: ColumnRef; el: HTMLElement }>();
    const anchors = reactive(new Map<string, Anchor>());
    const boardSize = reactive({ width: 0, height: 0 });

    let resizeObserver: ResizeObserver | null = null;

    function measure(): void {
        const board = boardEl.value;

        if (!board) {
            return;
        }

        const boardRect = board.getBoundingClientRect();
        boardSize.width = boardRect.width;
        boardSize.height = boardRect.height;

        for (const [key, dot] of dots) {
            const rect = dot.el.getBoundingClientRect();

            anchors.set(key, {
                x: rect.left + rect.width / 2 - boardRect.left,
                y: rect.top + rect.height / 2 - boardRect.top,
                side: sideFor(dot.ref),
            });
        }
    }

    function registerDot(ref: ColumnRef, el: HTMLElement): void {
        dots.set(columnRefKey(ref), { ref, el });
        requestAnimationFrame(measure);
    }

    function unregisterDot(ref: ColumnRef): void {
        dots.delete(columnRefKey(ref));
        anchors.delete(columnRefKey(ref));
    }

    function anchorFor(ref: ColumnRef): Anchor | undefined {
        return anchors.get(columnRefKey(ref));
    }

    function toBoardPoint(clientX: number, clientY: number): Point {
        const boardRect = boardEl.value?.getBoundingClientRect();

        if (!boardRect) {
            return { x: clientX, y: clientY };
        }

        return { x: clientX - boardRect.left, y: clientY - boardRect.top };
    }

    function nearestAnchor(point: Point, radius: number): ColumnRef | null {
        let best: { ref: ColumnRef; distance: number } | null = null;

        for (const [key, anchor] of anchors) {
            const distance = Math.hypot(anchor.x - point.x, anchor.y - point.y);

            if (distance <= radius && (!best || distance < best.distance)) {
                best = { ref: dots.get(key)!.ref, distance };
            }
        }

        return best?.ref ?? null;
    }

    onMounted(() => {
        measure();
        document.fonts?.ready.then(() => measure());
        window.addEventListener('resize', measure);

        if (boardEl.value) {
            resizeObserver = new ResizeObserver(() => measure());
            resizeObserver.observe(boardEl.value);
        }
    });

    onBeforeUnmount(() => {
        window.removeEventListener('resize', measure);
        resizeObserver?.disconnect();
    });

    return {
        anchors,
        boardSize,
        measure,
        registerDot,
        unregisterDot,
        anchorFor,
        toBoardPoint,
        nearestAnchor,
    };
}
