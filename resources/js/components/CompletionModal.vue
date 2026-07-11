<script setup lang="ts">
import { computed } from 'vue';
import StarRating from '@/components/StarRating.vue';
import { relationDescriptions } from '@/game/relations';
import type { Level } from '@/game/types';

const props = defineProps<{
    level: Level;
    stars: number;
    hasNext: boolean;
}>();

const emit = defineEmits<{ next: []; select: [] }>();

const statement = computed(() => {
    if (props.level.mode !== 'code') {
        return null;
    }

    return props.level.codeParts
        .map((part) => (typeof part === 'string' ? part : part.answer))
        .join('');
});

const praise = computed(() => {
    if (props.stars === 3) {
        return 'Flawless. The Eloquenter would be proud.';
    }

    return props.stars === 2
        ? 'Solid work. One more run for perfection?'
        : 'Solved! Replay it later to earn all stars.';
});
</script>

<template>
    <div
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-6 backdrop-blur-sm"
    >
        <div
            class="w-full max-w-md animate-pop-in rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        >
            <p class="font-mono text-xs tracking-widest text-accent uppercase">
                Level complete
            </p>
            <h2 class="mt-1 text-2xl font-bold text-slate-900 dark:text-white">
                {{ level.title }}
            </h2>

            <div class="my-6">
                <StarRating :stars="stars" animated size="lg" />
            </div>

            <p class="text-sm text-slate-500 dark:text-slate-400">
                {{ praise }}
            </p>

            <div
                class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left dark:border-slate-700 dark:bg-slate-800/60"
            >
                <p class="font-mono text-xs font-semibold text-accent">
                    {{ level.relation }}
                </p>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    {{ relationDescriptions[level.relation] }}
                </p>
                <code
                    v-if="statement"
                    class="mt-2 block overflow-x-auto font-mono text-xs text-slate-800 dark:text-slate-200"
                >
                    {{ statement }}
                </code>
            </div>

            <div class="mt-6 flex justify-center gap-3">
                <button
                    type="button"
                    class="rounded-xl border border-slate-200 px-5 py-2.5 font-medium text-slate-600 transition hover:border-slate-400 dark:border-slate-700 dark:text-slate-300"
                    @click="emit('select')"
                >
                    Level select
                </button>
                <button
                    v-if="hasNext"
                    type="button"
                    class="rounded-xl bg-accent px-5 py-2.5 font-semibold text-white shadow-lg shadow-accent/25 transition hover:brightness-110"
                    @click="emit('next')"
                >
                    Next level ▸
                </button>
            </div>
        </div>
    </div>
</template>
