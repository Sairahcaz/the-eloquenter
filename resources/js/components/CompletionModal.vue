<script setup lang="ts">
import { computed } from 'vue';
import ShareOnXButton from '@/components/ShareOnXButton.vue';
import StarRating from '@/components/StarRating.vue';
import { relationDescriptions } from '@/game/relations';
import type { Level, RelationType } from '@/game/types';

const props = defineProps<{
    level: Level;
    stars: number;
    relation: RelationType;
    statement: string | null;
    hasNext: boolean;
    shareText: string | null;
    finale: boolean;
}>();

const emit = defineEmits<{ next: []; select: [] }>();

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
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm sm:p-6"
    >
        <div
            class="max-h-full w-full max-w-md animate-pop-in overflow-y-auto rounded-2xl border border-slate-200 bg-white p-6 text-center shadow-2xl sm:p-8 dark:border-slate-700 dark:bg-slate-900"
        >
            <template v-if="finale">
                <p
                    class="font-mono text-xs tracking-widest text-accent uppercase"
                >
                    Game complete
                </p>
                <h2
                    class="mt-3 animate-pop-in font-display text-4xl tracking-wide text-balance sm:text-5xl"
                >
                    <span
                        class="bg-linear-to-r from-accent via-star to-accent bg-clip-text text-transparent drop-shadow-[0_0_18px_var(--color-accent)]"
                        >You are The Eloquenter.</span
                    >
                </h2>
            </template>
            <template v-else>
                <p
                    class="font-mono text-xs tracking-widest text-accent uppercase"
                >
                    Level complete
                </p>
                <h2
                    class="mt-1 text-2xl font-bold text-slate-900 dark:text-white"
                >
                    {{ level.title }}
                </h2>
            </template>

            <div class="my-6">
                <StarRating :stars="stars" animated size="lg" />
            </div>

            <p class="text-base text-slate-500 sm:text-sm dark:text-slate-400">
                {{
                    finale
                        ? 'Every relation mastered. Somewhere out there, raw SQL just got a little quieter.'
                        : praise
                }}
            </p>

            <div
                class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 text-left dark:border-slate-700 dark:bg-slate-800/60"
            >
                <p class="font-mono text-xs font-semibold text-accent">
                    {{ relation }}
                </p>
                <p
                    class="mt-1 text-base text-slate-600 sm:text-sm dark:text-slate-300"
                >
                    {{ relationDescriptions[relation] }}
                </p>
                <code
                    v-if="statement"
                    class="mt-2 block overflow-x-auto font-mono text-xs text-slate-800 dark:text-slate-200"
                >
                    {{ statement }}
                </code>
            </div>

            <div
                v-if="shareText"
                class="mt-5 flex flex-col items-center gap-2 rounded-xl border border-star/30 bg-star/10 p-4"
            >
                <p
                    class="text-sm font-semibold text-slate-700 dark:text-slate-200"
                >
                    Milestone unlocked! 🏆
                </p>
                <ShareOnXButton :text="shareText" />
            </div>

            <div class="mt-6 flex flex-wrap justify-center gap-3">
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
