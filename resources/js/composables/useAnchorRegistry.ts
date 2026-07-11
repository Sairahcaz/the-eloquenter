import { onBeforeUnmount, onMounted, reactive } from 'vue';
import type { Ref } from 'vue';
import type { Anchor, AnchorSide, Point } from '@/game/geometry';
import { columnRefKey } from '@/game/types';
import type { ColumnRef } from '@/game/types';

const SIDES: AnchorSide[] = ['left', 'right'];

/**
 * Tracks the dot elements on the board and turns them into board-relative
 * coordinates. A column can expose a dot on both sides at once, so entries
 * are keyed per side. Re-measures on resize and after webfonts load, since
 * both shift the card layout after the initial mount.
 */
export function useAnchorRegistry(boardEl: Ref<HTMLElement | null>) {
    const dots = new Map<
        string,
        { ref: ColumnRef; side: AnchorSide; el: HTMLElement }
    >();
    const anchors = reactive(new Map<string, Anchor>());
    const boardSize = reactive({ width: 0, height: 0 });

    let resizeObserver: ResizeObserver | null = null;

    function dotKey(ref: ColumnRef, side: AnchorSide): string {
        return `${columnRefKey(ref)}@${side}`;
    }

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
                side: dot.side,
            });
        }
    }

    function registerDot(
        ref: ColumnRef,
        side: AnchorSide,
        el: HTMLElement,
    ): void {
        dots.set(dotKey(ref, side), { ref, side, el });
        requestAnimationFrame(measure);
    }

    function unregisterDot(ref: ColumnRef, side: AnchorSide): void {
        dots.delete(dotKey(ref, side));
        anchors.delete(dotKey(ref, side));
    }

    function anchorsFor(ref: ColumnRef): Anchor[] {
        return SIDES.flatMap((side) => {
            const anchor = anchors.get(dotKey(ref, side));

            return anchor ? [anchor] : [];
        });
    }

    function anchorFor(ref: ColumnRef, towardX?: number): Anchor | undefined {
        const candidates = anchorsFor(ref);

        if (candidates.length <= 1 || towardX === undefined) {
            return candidates[0];
        }

        return candidates.reduce((best, candidate) =>
            Math.abs(candidate.x - towardX) < Math.abs(best.x - towardX)
                ? candidate
                : best,
        );
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
        anchorsFor,
        anchorFor,
        toBoardPoint,
        nearestAnchor,
    };
}
