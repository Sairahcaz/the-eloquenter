<script setup lang="ts">
import { computed } from 'vue';
import Leaderboard from '@/components/Leaderboard.vue';
import LevelNode from '@/components/LevelNode.vue';
import ProgressHud from '@/components/ProgressHud.vue';
import ShareOnXButton from '@/components/ShareOnXButton.vue';
import { useGameProgress } from '@/composables/useGameProgress';
import type { Chapter, HighscoreEntry, Level } from '@/game/types';

const props = defineProps<{
    chapters: Chapter[];
    highscores: HighscoreEntry[];
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

const progressShareText = computed(
    () =>
        `Learning Laravel Eloquent relationships the fun way: ${progress.totalStars.value}/${maxStars.value} stars ⭐ in The Eloquenter!`,
);
</script>

<template>
    <div class="mx-auto w-full max-w-6xl px-6 py-10">
        <div class="flex flex-col gap-8 lg:flex-row lg:gap-16">
            <aside
                class="w-full shrink-0 lg:sticky lg:top-10 lg:w-64 lg:self-start"
            >
                <Leaderboard
                    :highscores="highscores"
                    :player-name="progress.playerName.value"
                />
                <div class="mt-3 flex justify-center">
                    <ShareOnXButton :text="progressShareText" />
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col gap-8">
                <header class="flex flex-col gap-6">
                    <ProgressHud
                        :player-name="progress.playerName.value"
                        :total-stars="progress.totalStars.value"
                        :max-stars="maxStars"
                    />
                    <div>
                        <h1
                            class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white"
                        >
                            The <span class="text-accent">Eloquenter</span>
                        </h1>
                        <p class="mt-1 text-slate-500 dark:text-slate-400">
                            Pick a level. Connect the tables. Master the
                            relations.
                        </p>
                    </div>
                </header>

                <section
                    v-for="chapter in chapters"
                    :key="chapter.id"
                    class="flex flex-col gap-4"
                >
                    <div class="flex items-baseline gap-3">
                        <span class="font-mono text-sm font-bold text-accent">{{
                            String(chapter.id).padStart(2, '0')
                        }}</span>
                        <h2
                            class="text-xl font-semibold text-slate-900 dark:text-white"
                        >
                            {{ chapter.title }}
                        </h2>
                        <span
                            class="font-mono text-xs text-slate-400 dark:text-slate-500"
                            >{{ chapter.subtitle }}</span
                        >
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <LevelNode
                            v-for="level in chapter.levels"
                            :key="level.id"
                            :level="level"
                            :number="levelNumber(level)"
                            :locked="!isUnlocked(level)"
                            :completed="progress.isCompleted(level.id)"
                            :stars="progress.starsFor(level.id)"
                            @play="emit('play', level)"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>
