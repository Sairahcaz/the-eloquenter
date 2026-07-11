<script setup lang="ts">
import StarRating from '@/components/StarRating.vue';
import type { Level } from '@/game/types';

defineProps<{
    level: Level;
    number: number;
    locked: boolean;
    completed: boolean;
    stars: number;
}>();

const emit = defineEmits<{ play: [] }>();

const modeLabels = {
    connect: 'connect',
    guess: 'guess',
    code: 'code',
} as const;
</script>

<template>
    <button
        type="button"
        :disabled="locked"
        class="group flex w-28 flex-col items-center gap-1.5 rounded-2xl border p-3 text-center transition"
        :class="
            locked
                ? 'cursor-not-allowed border-slate-200 opacity-40 dark:border-slate-800'
                : 'border-slate-200 bg-white shadow-sm hover:-translate-y-0.5 hover:border-accent hover:shadow-md dark:border-slate-700 dark:bg-slate-900'
        "
        @click="emit('play')"
    >
        <span
            class="flex size-10 items-center justify-center rounded-full font-mono text-sm font-bold"
            :class="
                completed
                    ? 'bg-success/15 text-success'
                    : locked
                      ? 'bg-slate-100 text-slate-400 dark:bg-slate-800 dark:text-slate-500'
                      : 'bg-accent/10 text-accent'
            "
        >
            <svg
                v-if="locked"
                viewBox="0 0 24 24"
                class="size-4"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <rect x="5" y="11" width="14" height="9" rx="2" />
                <path d="M8 11V7a4 4 0 0 1 8 0v4" />
            </svg>
            <template v-else>{{ number }}</template>
        </span>
        <span
            class="line-clamp-2 text-xs font-medium text-slate-700 dark:text-slate-300"
            >{{ level.title }}</span
        >
        <span
            class="font-mono text-[10px] tracking-wide text-slate-400 uppercase dark:text-slate-500"
        >
            {{ modeLabels[level.mode] }}
        </span>
        <StarRating :stars="stars" />
    </button>
</template>
