<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { computed, onMounted, provide, reactive, ref } from 'vue';
import AnswerChoices from '@/components/AnswerChoices.vue';
import CodeCompletion from '@/components/CodeCompletion.vue';
import ConnectionLayer from '@/components/ConnectionLayer.vue';
import SolvedCodeSnippet from '@/components/SolvedCodeSnippet.vue';
import StarRating from '@/components/StarRating.vue';
import TableCard from '@/components/TableCard.vue';
import TaskBanner from '@/components/TaskBanner.vue';
import { useAnchorRegistry } from '@/composables/useAnchorRegistry';
import { useConnectionDrag } from '@/composables/useConnectionDrag';
import { useGameProgress } from '@/composables/useGameProgress';
import { boardApiKey } from '@/game/board';
import type { BoardApi, DotStatus } from '@/game/board';
import { connectionPath, dragPath } from '@/game/geometry';
import type { Anchor, AnchorSide, RenderedConnection } from '@/game/geometry';
import { relationDescriptions } from '@/game/relations';
import { columnRefKey, levelConnections, sameConnection } from '@/game/types';
import type {
    CodeJudgement,
    ColumnRef,
    ConnectionDef,
    ConnectionJudgement,
    GuessJudgement,
    Level,
    LevelResult,
    LevelSolution,
} from '@/game/types';
import {
    attempt as attemptRoute,
    connections as connectionsRoute,
    hint as hintRoute,
    solution as solutionRoute,
} from '@/routes/levels';

const props = defineProps<{ level: Level }>();

const emit = defineEmits<{ complete: [result: LevelResult]; back: [] }>();

const boardEl = ref<HTMLElement | null>(null);

const progress = useGameProgress();

const made = ref<ConnectionDef[]>([]);
const rejected = ref<ConnectionDef | null>(null);
const hintText = ref<string | null>(null);
const solved = ref(false);
const shakeSignals = reactive<Record<string, number>>({});

// A completed level opens in review: the earlier solution is displayed and
// no attempt starts until the player explicitly hits replay.
const reviewing = ref(progress.isCompleted(props.level.id));
const solution = ref<LevelSolution | null>(null);

const attemptHttp = useHttp({});
const connectionHttp = useHttp<
    { from: ColumnRef | null; to: ColumnRef | null },
    ConnectionJudgement
>({ from: null, to: null });
const hintHttp = useHttp<Record<string, never>, { hint: string }>({});
const solutionHttp = useHttp<Record<string, never>, LevelSolution>({});

// Every fresh level open resets the server-side attempt, so mistakes from
// an earlier run don't bleed into this one.
onMounted(() => {
    if (reviewing.value) {
        solutionHttp.get(solutionRoute.url(props.level.id), {
            onSuccess: (response) => (solution.value = response),
        });
    } else {
        attemptHttp.post(attemptRoute.url(props.level.id));
    }
});

function startReplay(): void {
    reviewing.value = false;
    solution.value = null;
    attemptHttp.post(attemptRoute.url(props.level.id));
}

const interactive = computed(
    () => props.level.mode === 'connect' && !solved.value && !reviewing.value,
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

// The connections drawn on the board without player input: the level's own
// display connections in guess/code mode, the fetched solution in review.
const shownConnections = computed<ConnectionDef[]>(() => {
    if (props.level.mode !== 'connect') {
        return levelConnections(props.level);
    }

    return reviewing.value ? (solution.value?.connections ?? []) : [];
});

function answerSide(ref: ColumnRef): AnchorSide {
    const ownCol = tableCols.value[ref.table] ?? 1;
    const connection = shownConnections.value.find((candidate) =>
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

// In connect mode the sides must not depend on the expected connections,
// otherwise the dot placement leaks which column belongs to which neighbour.
// Middle-column tables therefore expose every dot on both sides.
function dotSides(ref: ColumnRef): AnchorSide[] {
    if (props.level.mode !== 'connect') {
        return [answerSide(ref)];
    }

    const col = tableCols.value[ref.table] ?? 1;

    if (col === 1) {
        return ['right'];
    }

    if (col === 3) {
        return ['left'];
    }

    return ['left', 'right'];
}

const registry = useAnchorRegistry(boardEl);

const drag = useConnectionDrag({
    isEnabled: () => interactive.value,
    toBoardPoint: registry.toBoardPoint,
    nearestAnchor: registry.nearestAnchor,
    onConnect,
});

function onConnect(from: ColumnRef, to: ColumnRef): void {
    if (
        props.level.mode !== 'connect' ||
        solved.value ||
        connectionHttp.processing
    ) {
        return;
    }

    const candidate: ConnectionDef = { from, to };

    if (
        made.value.some((connection) => sameConnection(connection, candidate))
    ) {
        return;
    }

    connectionHttp.from = from;
    connectionHttp.to = to;
    connectionHttp.post(connectionsRoute.url(props.level.id), {
        onSuccess: (judgement) => {
            if (!judgement.correct) {
                rejected.value = candidate;
                shakeSignals[to.table] = (shakeSignals[to.table] ?? 0) + 1;
                setTimeout(() => (rejected.value = null), 700);

                return;
            }

            made.value.push(candidate);

            if (judgement.solved && judgement.stars && judgement.relation) {
                solve({
                    stars: judgement.stars,
                    relation: judgement.relation,
                    statement: null,
                });
            }
        },
    });
}

function solve(result: LevelResult): void {
    if (solved.value) {
        return;
    }

    solved.value = true;

    setTimeout(() => emit('complete', result), 700);
}

function handleGuessCorrect(judgement: GuessJudgement): void {
    if (judgement.stars && judgement.relation) {
        solve({
            stars: judgement.stars,
            relation: judgement.relation,
            statement: null,
        });
    }
}

function handleCodeCorrect(judgement: CodeJudgement): void {
    if (judgement.stars && judgement.relation) {
        solve({
            stars: judgement.stars,
            relation: judgement.relation,
            statement: judgement.statement,
        });
    }
}

function revealHint(): void {
    if (hintText.value || hintHttp.processing) {
        return;
    }

    hintHttp.post(hintRoute.url(props.level.id), {
        onSuccess: (response) => {
            hintText.value = response.hint;
        },
    });
}

const boardApi: BoardApi = {
    isInteractive: () => interactive.value,
    // In connect mode every column gets a dot; only key columns would give
    // the answer away. In review the answer is out anyway, so only the key
    // columns keep their dots, like in guess and code mode.
    allColumnsConnectable: () =>
        props.level.mode === 'connect' && !reviewing.value,
    registerDot: registry.registerDot,
    unregisterDot: registry.unregisterDot,
    dotSides,
    dotStatus(ref: ColumnRef): DotStatus {
        if (
            drag.dragFrom.value &&
            columnRefKey(drag.dragFrom.value) === columnRefKey(ref)
        ) {
            return 'dragging';
        }

        if (reviewing.value) {
            return shownConnections.value.some((connection) =>
                touchesRef(connection, ref),
            )
                ? 'connected'
                : 'idle';
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

    if (reviewing.value) {
        shownConnections.value.forEach((connection, index) =>
            items.push({ connection, state: 'made', delayMs: index * 220 }),
        );
    } else if (props.level.mode === 'connect') {
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
        shownConnections.value.forEach((connection, index) =>
            items.push({ connection, state: 'locked', delayMs: index * 220 }),
        );
    }

    return items.flatMap(({ connection, state, delayMs }) => {
        const pair = anchorPair(connection.from, connection.to);

        if (!pair) {
            return [];
        }

        return [
            {
                id: connectionId(connection),
                path: connectionPath(pair.from, pair.to),
                state,
                delayMs,
            },
        ];
    });
});

// With double-sided dots a connection has several anchor candidates; the
// pair with the shortest horizontal gap faces the other table.
function anchorPair(
    fromRef: ColumnRef,
    toRef: ColumnRef,
): { from: Anchor; to: Anchor } | null {
    let best: { from: Anchor; to: Anchor; distance: number } | null = null;

    for (const from of registry.anchorsFor(fromRef)) {
        for (const to of registry.anchorsFor(toRef)) {
            const distance = Math.abs(from.x - to.x);

            if (!best || distance < best.distance) {
                best = { from, to, distance };
            }
        }
    }

    return best;
}

const livePath = computed(() => {
    if (!drag.dragFrom.value) {
        return null;
    }

    const anchor = registry.anchorFor(drag.dragFrom.value, drag.cursor.x);

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
            :hint-text="hintText"
            :reviewing="reviewing"
            @back="emit('back')"
            @show-hint="revealHint"
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
            <div
                v-if="reviewing"
                class="mx-auto flex w-full max-w-2xl flex-col items-center gap-5"
            >
                <SolvedCodeSnippet
                    v-if="level.mode === 'code' && solution?.answers"
                    :level="level"
                    :answers="solution.answers"
                />
                <div
                    v-if="solution"
                    class="flex flex-col items-center gap-3 sm:flex-row"
                >
                    <span
                        class="shrink-0 rounded-xl border border-success bg-success/10 px-4 py-2 font-mono text-sm font-semibold text-success"
                    >
                        {{ solution.relation }}
                    </span>
                    <p
                        class="text-center text-sm text-slate-600 sm:text-left dark:text-slate-300"
                    >
                        {{ relationDescriptions[solution.relation] }}
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <StarRating :stars="progress.starsFor(level.id)" />
                    <button
                        type="button"
                        class="rounded-xl bg-accent px-5 py-2.5 font-semibold text-white shadow-lg shadow-accent/25 transition hover:brightness-110"
                        @click="startReplay"
                    >
                        Replay level ↺
                    </button>
                </div>
            </div>
            <p
                v-else-if="level.mode === 'connect'"
                class="text-center text-sm text-slate-400 dark:text-slate-500"
            >
                Drag from a glowing dot to the matching column.
                <span class="ml-2 font-mono text-xs"
                    >{{ made.length }}/{{
                        level.expectedCount
                    }}
                    connections</span
                >
            </p>
            <AnswerChoices
                v-else-if="level.mode === 'guess'"
                :level-id="level.id"
                :perspective="level.perspective"
                :choices="level.choices"
                @correct="handleGuessCorrect"
            />
            <CodeCompletion
                v-else
                :level="level"
                @correct="handleCodeCorrect"
            />
        </div>
    </div>
</template>
