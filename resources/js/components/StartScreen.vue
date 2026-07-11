<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{ start: [name: string] }>();

const name = ref('');

function submit(): void {
    if (name.value.trim()) {
        emit('start', name.value.trim());
    }
}
</script>

<template>
    <div
        class="flex min-h-screen flex-col items-center justify-center gap-8 px-6 py-10"
    >
        <h1 class="sr-only">The Eloquenter: Learn Eloquent, gamified</h1>

        <img
            src="/images/eloquenter-hero.jpg"
            alt="The Eloquenter: Learn Eloquent, gamified"
            class="w-full max-w-2xl rounded-2xl shadow-2xl shadow-slate-950/40"
        />

        <form
            class="flex w-full max-w-sm flex-col gap-3"
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
    </div>
</template>
