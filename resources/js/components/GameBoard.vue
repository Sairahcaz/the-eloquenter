<script setup lang="ts">
import { computed, provide, reactive, ref } from 'vue';
import AnswerChoices from '@/components/AnswerChoices.vue';
import CodeCompletion from '@/components/CodeCompletion.vue';
import ConnectionLayer from '@/components/ConnectionLayer.vue';
import TableCard from '@/components/TableCard.vue';
import TaskBanner from '@/components/TaskBanner.vue';
import { useAnchorRegistry } from '@/composables/useAnchorRegistry';
import { useConnectionDrag } from '@/composables/useConnectionDrag';
import type { Stars } from '@/composables/useGameProgress';
import { boardApiKey } from '@/game/board';
import type { BoardApi, DotStatus } from '@/game/board';
import { connectionPath, dragPath } from '@/game/geometry';
import type { AnchorSide, RenderedConnection } from '@/game/geometry';
import { columnRefKey, levelConnections, sameConnection } from '@/game/types';
import type { ColumnRef, ConnectionDef, Level } from '@/game/types';

const props = defineProps<{ level: Level }>();

const emit = defineEmits<{ complete: [stars: Stars]; back: [] }>();

const boardEl = ref<HTMLElement | null>(null);

const made = ref<ConnectionDef[]>([]);
const rejected = ref<ConnectionDef | null>(null);
const mistakes = ref(0);
const hintVisible = ref(false);
const solved = ref(false);
const shakeSignals = reactive<Record<string, number>>({});

const interactive = computed(
    () => props.level.mode === 'connect' && !solved.value,
);

const tableCols = computed(() =>
    Object.fromEntries(
        props.level.tables.map((table) => [table.id, table.position.col]),
    ),
);

function touchesRef(connection: ConnectionDef, ref: ColumnRef): boolean {
    return (
        columnRefKey(connection.from) === columnRefKey(ref) ||
        columnRefKey(connection.to) === columnRefKey(ref)
    );
}

function dotSide(ref: ColumnRef): AnchorSide {
    const ownCol = tableCols.value[ref.table] ?? 1;
    const connection = levelConnections(props.level).find((candidate) =>
        touchesRef(candidate, ref),
    );

    if (connection) {
        const other =
            columnRefKey(connection.from) === columnRefKey(ref)
                ? connection.to
                : connection.from;
        const otherCol = tableCols.value[other.table] ?? ownCol;

        if (otherCol !== ownCol) {
            return otherCol > ownCol ? 'right' : 'left';
        }
    }

    return ownCol === 3 ? 'left' : 'right';
}

const registry = useAnchorRegistry(boardEl, dotSide);

const drag = useConnectionDrag({
    isEnabled: () => interactive.value,
    toBoardPoint: registry.toBoardPoint,
    nearestAnchor: registry.nearestAnchor,
    onConnect,
});

function onConnect(from: ColumnRef, to: ColumnRef): void {
    if (props.level.mode !== 'connect' || solved.value) {
        return;
    }

    const candidate: ConnectionDef = { from, to };

    if (
        made.value.some((connection) => sameConnection(connection, candidate))
    ) {
        return;
    }

    const expected = props.level.expectedConnections.find((connection) =>
        sameConnection(connection, candidate),
    );

    if (expected) {
        made.value.push(expected);

        if (made.value.length === props.level.expectedConnections.length) {
            solve();
        }

        return;
    }

    mistakes.value++;
    rejected.value = candidate;
    shakeSignals[to.table] = (shakeSignals[to.table] ?? 0) + 1;
    setTimeout(() => (rejected.value = null), 700);
}

function solve(): void {
    if (solved.value) {
        return;
    }

    solved.value = true;

    const base = mistakes.value === 0 ? 3 : mistakes.value <= 2 ? 2 : 1;
    const stars = Math.min(base, hintVisible.value ? 2 : 3) as Stars;

    setTimeout(() => emit('complete', stars), 700);
}

const boardApi: BoardApi = {
    isInteractive: () => interactive.value,
    // In connect mode every column gets a dot; only key columns would give
    // the answer away.
    allColumnsConnectable: () => props.level.mode === 'connect',
    registerDot: registry.registerDot,
    unregisterDot: registry.unregisterDot,
    dotSide,
    dotStatus(ref: ColumnRef): DotStatus {
        if (
            drag.dragFrom.value &&
            columnRefKey(drag.dragFrom.value) === columnRefKey(ref)
        ) {
            return 'dragging';
        }

        if (
            props.level.mode === 'connect' &&
            made.value.some((connection) => touchesRef(connection, ref))
        ) {
            return 'connected';
        }

        return 'idle';
    },
    startDrag: drag.startDrag,
};

provide(boardApiKey, boardApi);

function connectionId(connection: ConnectionDef): string {
    return `${columnRefKey(connection.from)}->${columnRefKey(connection.to)}`;
}

const renderedConnections = computed<RenderedConnection[]>(() => {
    const items: Array<{
        connection: ConnectionDef;
        state: RenderedConnection['state'];
        delayMs: number;
    }> = [];

    if (props.level.mode === 'connect') {
        made.value.forEach((connection) =>
            items.push({ connection, state: 'made', delayMs: 0 }),
        );

        if (rejected.value) {
            items.push({
                connection: rejected.value,
                state: 'rejected',
                delayMs: 0,
            });
        }
    } else {
        props.level.shownConnections.forEach((connection, index) =>
            items.push({ connection, state: 'locked', delayMs: index * 220 }),
        );
    }

    return items.flatMap(({ connection, state, delayMs }) => {
        const from = registry.anchorFor(connection.from);
        const to = registry.anchorFor(connection.to);

        if (!from || !to) {
            return [];
        }

        return [
            {
                id: connectionId(connection),
                path: connectionPath(from, to),
                state,
                delayMs,
            },
        ];
    });
});

const livePath = computed(() => {
    if (!drag.dragFrom.value) {
        return null;
    }

    const anchor = registry.anchorFor(drag.dragFrom.value);

    return anchor ? dragPath(anchor, drag.cursor) : null;
});

const rowCount = computed(() =>
    Math.max(...props.level.tables.map((table) => table.position.row)),
);
</script>

<template>
    <div
        class="mx-auto flex min-h-screen w-full max-w-5xl flex-col gap-6 px-6 py-8"
    >
        <TaskBanner
            :level="level"
            :hint-visible="hintVisible"
            @back="emit('back')"
            @show-hint="hintVisible = true"
        />

        <div
            ref="boardEl"
            class="relative grid grid-cols-3 items-center justify-items-center gap-x-8 rounded-2xl border border-slate-200 bg-white/60 px-4 py-12 dark:border-slate-800 dark:bg-slate-900/40"
            :class="[
                interactive ? 'touch-none' : '',
                rowCount > 1 ? 'gap-y-12' : '',
            ]"
        >
            <TableCard
                v-for="table in level.tables"
                :key="table.id"
                :table="table"
                :shake-signal="shakeSignals[table.id]"
                :style="{
                    gridColumn: table.position.col,
                    gridRow: table.position.row,
                }"
            />
            <ConnectionLayer
                :width="registry.boardSize.width"
                :height="registry.boardSize.height"
                :connections="renderedConnections"
                :live-path="livePath"
            />
        </div>

        <div class="pb-8">
            <p
                v-if="level.mode === 'connect'"
                class="text-center text-sm text-slate-400 dark:text-slate-500"
            >
                Drag from a glowing dot to the matching column.
                <span class="ml-2 font-mono text-xs"
                    >{{ made.length }}/{{
                        level.expectedConnections.length
                    }}
                    connections</span
                >
            </p>
            <AnswerChoices
                v-else-if="level.mode === 'guess'"
                :perspective="level.perspective"
                :choices="level.choices"
                :answer="level.answer"
                @correct="solve"
                @mistake="mistakes++"
            />
            <CodeCompletion
                v-else
                :level="level"
                @correct="solve"
                @mistake="mistakes++"
            />
        </div>
    </div>
</template>
