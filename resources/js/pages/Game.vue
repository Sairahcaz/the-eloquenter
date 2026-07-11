<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import CompletionModal from '@/components/CompletionModal.vue';
import GameBoard from '@/components/GameBoard.vue';
import LevelSelect from '@/components/LevelSelect.vue';
import StartScreen from '@/components/StartScreen.vue';
import { useGameProgress } from '@/composables/useGameProgress';
import type { Stars } from '@/composables/useGameProgress';
import type { Chapter, Level } from '@/game/types';

const props = defineProps<{ chapters: Chapter[] }>();

const progress = useGameProgress();

type Screen = 'start' | 'select' | 'level';

// Starts on 'start' even for returning players: the server render has no
// localStorage, so anything else would cause a hydration mismatch.
const screen = ref<Screen>('start');
const currentLevel = ref<Level | null>(null);
const completionStars = ref<Stars | null>(null);

onMounted(() => {
    if (progress.playerName.value) {
        screen.value = 'select';
    }
});

const flatLevels = computed(() =>
    props.chapters.flatMap((chapter) => chapter.levels),
);

// Keying the wrapper remounts the subtree per screen/level; an animated
// <Transition> here stalled in throttled background tabs, so screens use a
// pure CSS enter animation instead.
const screenKey = computed(() =>
    screen.value === 'level' ? `level-${currentLevel.value?.id}` : screen.value,
);

const nextLevel = computed(() => {
    if (!currentLevel.value) {
        return null;
    }

    const index = flatLevels.value.findIndex(
        (level) => level.id === currentLevel.value!.id,
    );

    return flatLevels.value[index + 1] ?? null;
});

function handleStart(name: string): void {
    progress.setPlayerName(name);
    screen.value = 'select';
}

function handlePlay(level: Level): void {
    currentLevel.value = level;
    completionStars.value = null;
    screen.value = 'level';
}

function handleComplete(stars: Stars): void {
    if (currentLevel.value) {
        progress.recordCompletion(currentLevel.value.id, stars);
        completionStars.value = stars;
    }
}

function handleNext(): void {
    if (nextLevel.value) {
        handlePlay(nextLevel.value);
    } else {
        backToSelect();
    }
}

function backToSelect(): void {
    screen.value = 'select';
    currentLevel.value = null;
    completionStars.value = null;
}
</script>

<template>
    <Head title="The Eloquenter" />

    <div
        class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100"
    >
        <div :key="screenKey" class="screen-enter">
            <StartScreen v-if="screen === 'start'" @start="handleStart" />
            <LevelSelect
                v-else-if="screen === 'select'"
                :chapters="chapters"
                @play="handlePlay"
            />
            <GameBoard
                v-else-if="currentLevel"
                :level="currentLevel"
                @complete="handleComplete"
                @back="backToSelect"
            />
        </div>

        <CompletionModal
            v-if="completionStars && currentLevel"
            :level="currentLevel"
            :stars="completionStars"
            :has-next="nextLevel !== null"
            @next="handleNext"
            @select="backToSelect"
        />
    </div>
</template>
