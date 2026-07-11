<script setup lang="ts">
import { onMounted, ref } from 'vue';
import Leaderboard from '@/components/Leaderboard.vue';
import { useGameProgress } from '@/composables/useGameProgress';
import type { HighscoreEntry } from '@/game/types';

defineProps<{ highscores: HighscoreEntry[] }>();

const emit = defineEmits<{ start: [name: string] }>();

const progress = useGameProgress();

const name = ref('');

// Only revealed after mount: the server render has no localStorage, so
// showing the returning-player button immediately would break hydration.
const knownPlayer = ref('');

onMounted(() => {
    knownPlayer.value = progress.playerName.value;
});

function switchPlayer(): void {
    progress.resetProgress();
    knownPlayer.value = '';
}

// Fixed values instead of Math.random(): the start screen is server-rendered,
// so random positions would cause a hydration mismatch.
const embers = [
    { left: 6, size: 5, delay: 0, duration: 7 },
    { left: 14, size: 3, delay: 2.4, duration: 9 },
    { left: 23, size: 4, delay: 5.1, duration: 6.5 },
    { left: 31, size: 3, delay: 1.2, duration: 8 },
    { left: 42, size: 5, delay: 3.8, duration: 7.5 },
    { left: 55, size: 3, delay: 0.6, duration: 9.5 },
    { left: 63, size: 4, delay: 4.4, duration: 6 },
    { left: 71, size: 3, delay: 2.1, duration: 8.5 },
    { left: 79, size: 5, delay: 6.2, duration: 7 },
    { left: 86, size: 3, delay: 1.7, duration: 9 },
    { left: 93, size: 4, delay: 3.3, duration: 6.8 },
    { left: 48, size: 3, delay: 5.6, duration: 8.2 },
];

function submit(): void {
    if (name.value.trim()) {
        emit('start', name.value.trim());
    }
}
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center gap-8 overflow-hidden px-6 py-10"
    >
        <h1 class="sr-only">The Eloquenter: Learn Eloquent, gamified</h1>

        <div class="relative w-full max-w-2xl">
            <img
                src="/images/eloquenter-hero.jpg"
                alt=""
                aria-hidden="true"
                class="absolute -inset-3 size-full scale-105 rounded-2xl opacity-55 blur-2xl saturate-150 motion-safe:animate-glow-flicker"
            />
            <img
                src="/images/eloquenter-hero.jpg"
                alt="The Eloquenter: Learn Eloquent, gamified"
                class="relative w-full rounded-2xl shadow-2xl shadow-slate-950/40 motion-safe:animate-hero-zoom"
            />
            <span
                v-for="(ember, index) in embers"
                :key="index"
                aria-hidden="true"
                class="ember absolute bottom-0 animate-ember rounded-full motion-reduce:hidden"
                :class="index % 2 === 0 ? 'bg-accent' : 'bg-star'"
                :style="{
                    left: `${ember.left}%`,
                    width: `${ember.size}px`,
                    height: `${ember.size}px`,
                    animationDelay: `${ember.delay}s`,
                    animationDuration: `${ember.duration}s`,
                }"
            />
        </div>

        <p
            class="max-w-full text-center font-display text-lg tracking-wider whitespace-nowrap text-slate-600 sm:text-2xl dark:text-slate-400"
        >
            Some people fix databases with queries. He fixes them with
            <span class="font-mono text-[0.7em] text-accent"
                >$this-&gt;relations()</span
            >.
        </p>

        <div
            v-if="knownPlayer"
            class="relative z-10 flex w-full max-w-sm flex-col gap-3"
        >
            <button
                type="button"
                class="rounded-xl bg-accent px-4 py-3 text-lg font-semibold text-white shadow-lg shadow-accent/25 transition hover:brightness-110 active:scale-[0.98]"
                @click="emit('start', knownPlayer)"
            >
                Continue as {{ knownPlayer }} ▸
            </button>
            <button
                type="button"
                class="text-sm text-slate-500 transition hover:text-accent dark:text-slate-400"
                @click="switchPlayer"
            >
                Not {{ knownPlayer }}? Start fresh (resets progress)
            </button>
        </div>

        <form
            v-else
            class="relative z-10 flex w-full max-w-sm flex-col gap-3"
            @submit.prevent="submit"
        >
            <label class="sr-only" for="player-name">Your name</label>
            <input
                id="player-name"
                v-model="name"
                type="text"
                placeholder="Enter your name"
                autocomplete="off"
                maxlength="30"
                class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-center text-lg text-slate-900 shadow-sm outline-none focus:border-accent focus:ring-2 focus:ring-accent/40 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
            />
            <button
                type="submit"
                :disabled="!name.trim()"
                class="rounded-xl bg-accent px-4 py-3 text-lg font-semibold text-white shadow-lg shadow-accent/25 transition hover:brightness-110 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Start learning
            </button>
        </form>

        <Leaderboard
            v-if="highscores.length > 0"
            :highscores="highscores"
            class="w-full max-w-sm"
        />
    </div>
</template>
