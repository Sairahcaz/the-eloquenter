<script setup lang="ts">
import type { Level } from '@/game/types';

defineProps<{
    level: Level;
    hintVisible: boolean;
}>();

const emit = defineEmits<{ back: []; showHint: [] }>();
</script>

<template>
    <div class="flex flex-col gap-3">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <button
                    type="button"
                    aria-label="Back to level select"
                    class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-accent hover:text-accent dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400"
                    @click="emit('back')"
                >
                    <svg
                        viewBox="0 0 24 24"
                        class="size-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path d="M15 6l-6 6 6 6" />
                    </svg>
                </button>
                <div>
                    <h2
                        class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                    >
                        {{ level.title }}
                    </h2>
                    <p class="mt-1 text-slate-600 dark:text-slate-400">
                        {{ level.task }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <span
                    v-if="level.mode === 'connect'"
                    class="rounded-full bg-accent/10 px-3 py-1 font-mono text-xs font-semibold text-accent"
                >
                    {{ level.relation }}
                </span>
                <button
                    v-if="level.hint && !hintVisible"
                    type="button"
                    class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-500 transition hover:border-star hover:text-star dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400"
                    @click="emit('showHint')"
                >
                    Hint (max ★★)
                </button>
            </div>
        </div>
        <p
            v-if="hintVisible && level.hint"
            class="rounded-lg border border-star/30 bg-star/10 px-3 py-2 text-sm text-slate-700 dark:text-slate-300"
        >
            💡 {{ level.hint }}
        </p>
    </div>
</template>
