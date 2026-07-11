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

function submit(): void {
    if (name.value.trim()) {
        emit('start', name.value.trim());
    }
}
</script>

<template>
    <div
        class="relative flex min-h-screen items-center justify-center overflow-hidden px-6 py-10"
    >
        <div
            class="absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-accent/15 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="absolute top-1/4 -left-48 size-[28rem] rounded-full bg-accent/10 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="absolute top-1/3 -right-48 size-[24rem] rounded-full bg-accent/10 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="absolute -bottom-56 left-1/4 size-[32rem] rounded-full bg-accent/10 blur-3xl"
            aria-hidden="true"
        />

        <div
            class="relative w-full max-w-3xl overflow-hidden rounded-3xl border border-orange-950/10 bg-white shadow-2xl shadow-orange-950/15 dark:border-white/10 dark:bg-slate-900"
        >
            <div class="relative">
                <img
                    src="/images/eloquenter-hero.jpg"
                    alt="The Eloquenter: Learn Eloquent, gamified"
                    class="max-h-72 w-full object-cover object-top"
                />
                <div
                    class="absolute inset-x-0 bottom-0 h-24 bg-linear-to-t from-white dark:from-slate-900"
                    aria-hidden="true"
                />
            </div>

            <div
                class="grid gap-8 p-8 sm:p-10"
                :class="highscores.length > 0 ? 'sm:grid-cols-2' : ''"
            >
                <div class="flex flex-col gap-4">
                    <div>
                        <h1
                            class="font-display text-4xl tracking-wide text-balance text-slate-900 dark:text-white"
                        >
                            The <span class="text-accent">Eloquenter</span>
                        </h1>
                        <p
                            class="mt-2 text-pretty text-slate-500 dark:text-slate-400"
                        >
                            Some people write caveman SQL. You write:
                            <span
                                class="font-mono text-xs wrap-anywhere text-accent"
                            >
                                $user-&gt;posts()-&gt;with('comments.author') </span
                            >.
                        </p>
                    </div>

                    <div v-if="knownPlayer" class="flex flex-col gap-3">
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
                        class="flex flex-col gap-3"
                        @submit.prevent="submit"
                    >
                        <label class="sr-only" for="player-name"
                            >Your name</label
                        >
                        <input
                            id="player-name"
                            v-model="name"
                            type="text"
                            placeholder="Enter your name"
                            autocomplete="off"
                            maxlength="30"
                            class="rounded-xl border border-orange-950/15 bg-white px-4 py-3 text-center text-lg text-slate-900 shadow-sm outline-none focus:border-accent focus:ring-2 focus:ring-accent/40 dark:border-white/15 dark:bg-slate-950 dark:text-white"
                        />
                        <button
                            type="submit"
                            :disabled="!name.trim()"
                            class="rounded-xl bg-accent px-4 py-3 text-lg font-semibold text-white shadow-lg shadow-accent/25 transition hover:brightness-110 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Start learning
                        </button>
                    </form>
                </div>

                <Leaderboard
                    v-if="highscores.length > 0"
                    :highscores="highscores"
                    class="self-start"
                />
            </div>
        </div>
    </div>
</template>
