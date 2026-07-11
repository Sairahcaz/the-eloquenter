<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CompletionModal from '@/components/CompletionModal.vue';
import GameBoard from '@/components/GameBoard.vue';
import LevelSelect from '@/components/LevelSelect.vue';
import StartScreen from '@/components/StartScreen.vue';
import { useGameProgress } from '@/composables/useGameProgress';
import type { Stars } from '@/composables/useGameProgress';
import type { Chapter, HighscoreEntry, Level } from '@/game/types';
import { store as storeHighscore } from '@/routes/highscores';

const props = defineProps<{
    chapters: Chapter[];
    highscores: HighscoreEntry[];
}>();

const progress = useGameProgress();

type Screen = 'start' | 'select' | 'level';

// Everyone lands on the start screen; returning players get a
// "Continue as ..." button there instead of being skipped ahead.
const screen = ref<Screen>('start');
const currentLevel = ref<Level | null>(null);
const completionStars = ref<Stars | null>(null);
const shareText = ref<string | null>(null);

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
    shareText.value = null;
    screen.value = 'level';
}

// Sharing after every level would be noise; only chapter completions and
// finishing the game are brag-worthy milestones.
function milestoneShareText(completedLevel: Level): string | null {
    const stars = progress.totalStars.value;
    const max = flatLevels.value.length * 3;

    if (flatLevels.value.every((level) => progress.isCompleted(level.id))) {
        return `I mastered every Eloquent relationship in The Eloquenter: ${stars}/${max} stars 🔥 Learn Laravel Eloquent, gamified!`;
    }

    const chapter = props.chapters.find((candidate) =>
        candidate.levels.some((level) => level.id === completedLevel.id),
    );

    if (
        chapter &&
        chapter.levels.every((level) => progress.isCompleted(level.id))
    ) {
        return `Chapter "${chapter.title}" completed in The Eloquenter with ${stars}/${max} stars ⭐ Learning Laravel Eloquent relationships, gamified!`;
    }

    return null;
}

function handleComplete(stars: Stars): void {
    if (!currentLevel.value) {
        return;
    }

    progress.recordCompletion(currentLevel.value.id, stars);
    completionStars.value = stars;
    shareText.value = milestoneShareText(currentLevel.value);

    router.post(
        storeHighscore.url(),
        { name: progress.playerName.value, stars: progress.totalStars.value },
        { preserveState: true, preserveScroll: true },
    );
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
    shareText.value = null;
}
</script>

<template>
    <Head title="The Eloquenter" />

    <div
        class="relative min-h-screen bg-amber-50 font-sans text-slate-900 antialiased dark:bg-stone-950 dark:text-slate-100"
    >
        <svg
            class="pointer-events-none absolute inset-0 size-full text-accent/10"
            aria-hidden="true"
        >
            <defs>
                <pattern
                    id="app-dots"
                    width="24"
                    height="24"
                    patternUnits="userSpaceOnUse"
                >
                    <circle cx="1.5" cy="1.5" r="1.5" fill="currentColor" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#app-dots)" />
        </svg>
        <div :key="screenKey" class="screen-enter relative">
            <StartScreen
                v-if="screen === 'start'"
                :highscores="highscores"
                @start="handleStart"
            />
            <LevelSelect
                v-else-if="screen === 'select'"
                :chapters="chapters"
                :highscores="highscores"
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
            :share-text="shareText"
            @next="handleNext"
            @select="backToSelect"
        />
    </div>
</template>
