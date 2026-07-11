<script setup lang="ts">
import { computed, inject, onBeforeUnmount, onMounted, ref } from 'vue';
import { boardApiKey } from '@/game/board';
import type { ColumnRef, TableColumn } from '@/game/types';

const props = defineProps<{
    tableId: string;
    column: TableColumn;
}>();

const board = inject(boardApiKey)!;

const dotEl = ref<HTMLElement | null>(null);

const columnRef = computed<ColumnRef>(() => ({
    table: props.tableId,
    column: props.column.name,
}));

const hasDot = computed(
    () => props.column.key === 'primary' || props.column.key === 'foreign',
);

const side = computed(() => board.dotSide(columnRef.value));

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

onMounted(() => {
    if (dotEl.value) {
        board.registerDot(columnRef.value, dotEl.value);
    }
});

onBeforeUnmount(() => {
    if (hasDot.value) {
        board.unregisterDot(columnRef.value);
    }
});
</script>

<template>
    <li class="relative flex items-center justify-between gap-3 px-3 py-1.5">
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

        <button
            v-if="hasDot"
            ref="dotEl"
            type="button"
            data-dot
            :data-table="tableId"
            :data-column="column.name"
            :aria-label="`Connect ${tableId}.${column.name}`"
            class="absolute top-1/2 -translate-y-1/2 touch-none p-2"
            :class="side === 'left' ? '-left-5' : '-right-5'"
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
                    'scale-125 border-accent bg-accent': status === 'dragging',
                }"
            />
        </button>
    </li>
</template>
