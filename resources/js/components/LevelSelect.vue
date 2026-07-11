<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Leaderboard from '@/components/Leaderboard.vue';
import ShareOnXButton from '@/components/ShareOnXButton.vue';
import StarRating from '@/components/StarRating.vue';
import { useGameProgress } from '@/composables/useGameProgress';
import type { Chapter, HighscoreEntry, Level, Paginated } from '@/game/types';
import { home } from '@/routes';

const props = defineProps<{
    chapters: Chapter[];
    highscores: Paginated<HighscoreEntry>;
}>();

const emit = defineEmits<{ play: [level: Level] }>();

const progress = useGameProgress();

const flatLevels = computed(() =>
    props.chapters.flatMap((chapter) => chapter.levels),
);

const maxStars = computed(() => flatLevels.value.length * 3);

function isUnlocked(level: Level): boolean {
    const index = flatLevels.value.findIndex(
        (candidate) => candidate.id === level.id,
    );

    return index <= 0 || progress.isCompleted(flatLevels.value[index - 1].id);
}

function levelNumber(level: Level): number {
    return (
        flatLevels.value.findIndex((candidate) => candidate.id === level.id) + 1
    );
}

const modeLabels = {
    connect: 'connect',
    guess: 'guess',
    code: 'code',
} as const;

// Node backgrounds must stay opaque, otherwise the dashed path line
// shines through the circles.
function nodeCircleClasses(level: Level): string {
    if (progress.isCompleted(level.id)) {
        return 'border-success bg-amber-50 bg-linear-to-b from-success/20 to-success/20 text-success dark:bg-stone-950';
    }

    if (isUnlocked(level)) {
        return 'border-accent bg-white text-accent dark:bg-slate-900';
    }

    return 'border-slate-300 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-600';
}

function goToHighscorePage(page: number): void {
    router.get(
        home.url({ query: { page } }),
        {},
        { only: ['highscores'], preserveState: true, preserveScroll: true },
    );
}

const progressShareText = computed(
    () =>
        `Learning Laravel Eloquent relationships the fun way: ${progress.totalStars.value}/${maxStars.value} stars ⭐ in The Eloquenter!`,
);
</script>

<template>
    <div class="mx-auto w-full max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-10">
            <header class="flex flex-wrap items-end justify-between gap-6">
                <div>
                    <h1
                        class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        The <span class="text-accent">Eloquenter</span>
                    </h1>
                    <p class="mt-1 text-slate-500 dark:text-slate-400">
                        Follow the path. Connect the tables. Master the
                        relations.
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <p
                        class="font-display text-3xl tracking-wide text-slate-900 dark:text-white"
                    >
                        Agent
                        <span class="text-accent">{{
                            progress.playerName.value
                        }}</span>
                    </p>
                    <span
                        class="h-8 w-px bg-slate-950/10 dark:bg-white/10"
                        aria-hidden="true"
                    />
                    <p
                        class="flex items-center gap-2 font-display text-3xl tracking-wide text-slate-900 tabular-nums dark:text-white"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            class="size-6 text-star"
                            fill="currentColor"
                        >
                            <path
                                d="M12 2l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.02l-5.88 3.09 1.12-6.55L2.48 8.92l6.58-.96L12 2z"
                            />
                        </svg>
                        {{ progress.totalStars.value
                        }}<span
                            class="text-xl text-slate-400 dark:text-slate-500"
                            >/{{ maxStars }}</span
                        >
                    </p>
                </div>
            </header>

            <div class="flex flex-col gap-10 lg:flex-row lg:gap-16">
                <div class="relative min-w-0 flex-1">
                    <div
                        class="absolute inset-y-6 left-6 -translate-x-px border-l-2 border-dashed border-slate-300 dark:border-slate-700"
                        aria-hidden="true"
                    />
                    <div class="flex flex-col gap-10">
                        <section
                            v-for="chapter in chapters"
                            :key="chapter.id"
                            class="flex flex-col gap-4"
                        >
                            <div class="relative flex items-center gap-4">
                                <span
                                    class="z-10 flex size-12 shrink-0 items-center justify-center rounded-full border-2 border-accent bg-amber-50 font-mono text-sm font-bold text-accent dark:bg-stone-950"
                                >
                                    {{ String(chapter.id).padStart(2, '0') }}
                                </span>
                                <div>
                                    <h2
                                        class="text-xl font-semibold text-slate-900 dark:text-white"
                                    >
                                        {{ chapter.title }}
                                    </h2>
                                    <p
                                        class="font-mono text-xs text-slate-400 dark:text-slate-500"
                                    >
                                        {{ chapter.subtitle }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex flex-col gap-3">
                                <button
                                    v-for="level in chapter.levels"
                                    :key="level.id"
                                    type="button"
                                    :disabled="!isUnlocked(level)"
                                    class="group relative flex items-center gap-4 text-left"
                                    :class="
                                        isUnlocked(level)
                                            ? ''
                                            : 'cursor-not-allowed'
                                    "
                                    @click="emit('play', level)"
                                >
                                    <span
                                        class="z-10 flex size-12 shrink-0 items-center justify-center rounded-full border-2 font-mono text-sm font-bold transition"
                                        :class="nodeCircleClasses(level)"
                                    >
                                        <svg
                                            v-if="!isUnlocked(level)"
                                            viewBox="0 0 24 24"
                                            class="size-4"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <rect
                                                x="5"
                                                y="11"
                                                width="14"
                                                height="9"
                                                rx="2"
                                            />
                                            <path d="M8 11V7a4 4 0 0 1 8 0v4" />
                                        </svg>
                                        <template v-else>{{
                                            levelNumber(level)
                                        }}</template>
                                    </span>
                                    <span
                                        class="flex min-w-0 flex-1 items-center justify-between gap-3 rounded-xl border px-4 py-3 transition"
                                        :class="
                                            isUnlocked(level)
                                                ? 'border-slate-200 bg-white shadow-sm group-hover:-translate-y-0.5 group-hover:border-accent group-hover:shadow-md dark:border-slate-700 dark:bg-slate-900'
                                                : 'border-slate-300 bg-slate-100 opacity-60 dark:border-slate-700 dark:bg-slate-900'
                                        "
                                    >
                                        <span class="min-w-0">
                                            <span
                                                class="block truncate text-sm font-medium text-slate-700 dark:text-slate-300"
                                                >{{ level.title }}</span
                                            >
                                            <span
                                                class="font-mono text-[10px] tracking-wide text-slate-400 uppercase dark:text-slate-500"
                                                >{{
                                                    modeLabels[level.mode]
                                                }}</span
                                            >
                                        </span>
                                        <StarRating
                                            :stars="progress.starsFor(level.id)"
                                        />
                                    </span>
                                </button>
                            </div>
                        </section>
                    </div>
                </div>

                <aside
                    class="w-full shrink-0 lg:sticky lg:top-10 lg:w-64 lg:self-start"
                >
                    <Leaderboard
                        :highscores="highscores.data"
                        :player-name="progress.playerName.value"
                        :start-rank="
                            (highscores.current_page - 1) *
                                highscores.per_page +
                            1
                        "
                    />
                    <div
                        v-if="highscores.last_page > 1"
                        class="mt-3 flex items-center justify-between"
                    >
                        <button
                            type="button"
                            :disabled="highscores.current_page === 1"
                            class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:border-accent hover:text-accent disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-400"
                            @click="
                                goToHighscorePage(highscores.current_page - 1)
                            "
                        >
                            ◂ Prev
                        </button>
                        <span
                            class="font-mono text-xs text-slate-400 tabular-nums dark:text-slate-500"
                            >{{ highscores.current_page }}/{{
                                highscores.last_page
                            }}</span
                        >
                        <button
                            type="button"
                            :disabled="
                                highscores.current_page === highscores.last_page
                            "
                            class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-medium text-slate-500 transition hover:border-accent hover:text-accent disabled:cursor-not-allowed disabled:opacity-40 dark:border-slate-700 dark:text-slate-400"
                            @click="
                                goToHighscorePage(highscores.current_page + 1)
                            "
                        >
                            Next ▸
                        </button>
                    </div>
                    <div class="mt-3 flex justify-center">
                        <ShareOnXButton :text="progressShareText" />
                    </div>
                </aside>
            </div>
        </div>
    </div>
</template>
