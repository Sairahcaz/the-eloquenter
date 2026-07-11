<script setup lang="ts">
import { computed } from 'vue';
import LevelNode from '@/components/LevelNode.vue';
import ProgressHud from '@/components/ProgressHud.vue';
import { useGameProgress } from '@/composables/useGameProgress';
import type { Chapter, Level } from '@/game/types';

const props = defineProps<{ chapters: Chapter[] }>();

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
</script>

<template>
    <div
        class="mx-auto flex min-h-screen w-full max-w-4xl flex-col gap-8 px-6 py-10"
    >
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
                    Pick a level. Connect the tables. Master the relations.
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
</template>
