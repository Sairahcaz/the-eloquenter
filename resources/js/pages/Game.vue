<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import CompletionModal from '@/components/CompletionModal.vue';
import GameBoard from '@/components/GameBoard.vue';
import LevelSelect from '@/components/LevelSelect.vue';
import StartScreen from '@/components/StartScreen.vue';
import {
    rememberIdentity,
    storedIdentity,
    useGameProgress,
} from '@/composables/useGameProgress';
import type {
    Chapter,
    HighscoreEntry,
    Level,
    LevelResult,
    Paginated,
    PlayerIdentity,
} from '@/game/types';
import { store as storePlayer } from '@/routes/players';

const props = defineProps<{
    chapters: Chapter[];
    player: PlayerIdentity | null;
    completions: Record<string, number>;
    topHighscores: HighscoreEntry[];
    highscores: Paginated<HighscoreEntry>;
}>();

const progress = useGameProgress();

type Screen = 'start' | 'select' | 'level';

// Everyone lands on the start screen; returning players get a
// "Continue as ..." button there instead of being skipped ahead.
const screen = ref<Screen>('start');
const currentLevel = ref<Level | null>(null);
const completionResult = ref<LevelResult | null>(null);
const shareText = ref<string | null>(null);
const finale = ref(false);

const flatLevels = computed(() =>
    props.chapters.flatMap((chapter) => chapter.levels),
);

// Keying the wrapper remounts the subtree per screen/level; an animated
// <Transition> here stalled in throttled background tabs, so screens use a
// pure CSS enter animation instead.
const screenKey = computed(() =>
    screen.value === 'level' ? `level-${currentLevel.value?.id}` : screen.value,
);

// Only levels within the same chapter chain directly; after a chapter's
// last level the player returns to the map before the next section starts.
const nextLevel = computed(() => {
    if (!currentLevel.value) {
        return null;
    }

    const chapter = props.chapters.find((candidate) =>
        candidate.levels.some((level) => level.id === currentLevel.value!.id),
    );

    if (!chapter) {
        return null;
    }

    const index = chapter.levels.findIndex(
        (level) => level.id === currentLevel.value!.id,
    );

    return chapter.levels[index + 1] ?? null;
});

function handleStart(name: string): void {
    // The session already knows this player; no need to join again.
    if (props.player && props.player.name === name) {
        rememberIdentity(props.player);
        screen.value = 'select';

        return;
    }

    const stored = storedIdentity();

    router.post(
        storePlayer.url(),
        // The token resumes an existing player; a different name means the
        // visitor wants a fresh start.
        { name, token: stored && stored.name === name ? stored.token : null },
        {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                if (props.player) {
                    rememberIdentity(props.player);
                }

                screen.value = 'select';
            },
        },
    );
}

function handlePlay(level: Level): void {
    currentLevel.value = level;
    completionResult.value = null;
    shareText.value = null;
    finale.value = false;
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

function handleComplete(result: LevelResult): void {
    if (!currentLevel.value) {
        return;
    }

    const completedLevel = currentLevel.value;
    completionResult.value = result;

    // The server already recorded the completion; refresh progress and
    // leaderboard props before judging milestone share-worthiness.
    router.reload({
        only: ['completions', 'highscores', 'topHighscores'],
        onSuccess: () => {
            shareText.value = milestoneShareText(completedLevel);
            finale.value =
                completedLevel.id === flatLevels.value.at(-1)?.id &&
                flatLevels.value.every((level) =>
                    progress.isCompleted(level.id),
                );
        },
    });
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
    completionResult.value = null;
    shareText.value = null;
    finale.value = false;
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
                :highscores="topHighscores"
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
            v-if="completionResult && currentLevel"
            :level="currentLevel"
            :stars="completionResult.stars"
            :relation="completionResult.relation"
            :statement="completionResult.statement"
            :has-next="nextLevel !== null"
            :share-text="shareText"
            :finale="finale"
            @next="handleNext"
            @select="backToSelect"
        />
    </div>
</template>
