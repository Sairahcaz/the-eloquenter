<script setup lang="ts">
import { computed, inject } from 'vue';
import { boardApiKey } from '@/game/board';
import type { AnchorSide } from '@/game/geometry';
import type { ColumnRef, TableColumn } from '@/game/types';

const props = defineProps<{
    tableId: string;
    column: TableColumn;
}>();

const board = inject(boardApiKey)!;

const dotEls = new Map<AnchorSide, HTMLElement>();

const columnRef = computed<ColumnRef>(() => ({
    table: props.tableId,
    column: props.column.name,
}));

const hasDot = computed(
    () =>
        board.allColumnsConnectable() ||
        props.column.key === 'primary' ||
        props.column.key === 'foreign',
);

const sides = computed(() => board.dotSides(columnRef.value));

const status = computed(() => board.dotStatus(columnRef.value));

const badgeStyles: Record<string, string> = {
    primary: 'bg-pk/15 text-pk',
    foreign: 'bg-fk/15 text-fk',
    morph: 'bg-morph/15 text-morph',
};

const badgeLabels: Record<string, string> = {
    primary: 'PK',
    foreign: 'FK',
    morph: 'TYPE',
};

// Registering directly in the template-ref callback keeps the registry in
// sync when dots appear or disappear after mount, e.g. when a review board
// switches into replay mode.
function setDotEl(side: AnchorSide, el: unknown): void {
    if (el instanceof HTMLElement) {
        if (dotEls.get(side) !== el) {
            dotEls.set(side, el);
            board.registerDot(columnRef.value, side, el);
        }
    } else if (dotEls.has(side)) {
        dotEls.delete(side);
        board.unregisterDot(columnRef.value, side);
    }
}
</script>

<template>
    <li
        class="relative flex items-center justify-between gap-3 px-3 py-2 sm:py-1.5"
    >
        <span
            class="flex items-center gap-1.5 font-mono text-xs text-slate-700 dark:text-slate-300"
        >
            {{ column.name }}
            <span
                v-if="column.key"
                class="rounded px-1 py-px font-sans text-[9px] font-bold tracking-wide"
                :class="badgeStyles[column.key]"
            >
                {{ badgeLabels[column.key] }}
            </span>
        </span>
        <span
            class="font-mono text-[10px] text-slate-400 dark:text-slate-500"
            >{{ column.type }}</span
        >

        <template v-if="hasDot">
            <button
                v-for="side in sides"
                :key="side"
                :ref="(el) => setDotEl(side, el)"
                type="button"
                data-dot
                :data-table="tableId"
                :data-column="column.name"
                :aria-label="`Connect ${tableId}.${column.name}`"
                class="absolute top-1/2 -translate-y-1/2 touch-none p-2.5 sm:p-2"
                :class="
                    side === 'left'
                        ? '-left-4 sm:-left-3.5'
                        : '-right-4 sm:-right-3.5'
                "
                @pointerdown="board.startDrag(columnRef, $event)"
            >
                <span
                    class="block size-3 rounded-full border-2 transition"
                    :class="{
                        'border-slate-400 bg-white dark:border-slate-500 dark:bg-slate-950':
                            status === 'idle' && !board.isInteractive(),
                        'animate-dot-pulse cursor-grab border-accent bg-white dark:bg-slate-950':
                            status === 'idle' && board.isInteractive(),
                        'border-success bg-success': status === 'connected',
                        'scale-125 border-accent bg-accent':
                            status === 'dragging',
                    }"
                />
            </button>
        </template>
    </li>
</template>
