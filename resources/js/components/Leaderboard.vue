<script setup lang="ts">
import type { HighscoreEntry } from '@/game/types';

withDefaults(
    defineProps<{
        highscores: HighscoreEntry[];
        playerName?: string;
        startRank?: number;
    }>(),
    { playerName: '', startRank: 1 },
);

function formatDuration(seconds: number): string {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    const pad = (n: number) => String(n).padStart(2, '0');

    return h > 0 ? `${h}:${pad(m)}:${pad(s)}` : `${m}:${pad(s)}`;
}
</script>

<template>
    <section
        class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-900"
    >
        <h2
            class="flex items-center gap-2 text-sm font-semibold tracking-wide text-slate-500 uppercase dark:text-slate-400"
        >
            <svg
                viewBox="0 0 24 24"
                class="size-4 text-star"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
            >
                <path d="M8 21h8M12 17v4M7 4h10v4a5 5 0 0 1-10 0V4z" />
                <path d="M7 6H4a2 2 0 0 0 2 4h1M17 6h3a2 2 0 0 1-2 4h-1" />
            </svg>
            Leaderboard
        </h2>
        <p
            v-if="highscores.length === 0"
            class="mt-3 text-sm text-slate-400 dark:text-slate-500"
        >
            No scores yet. Be the first!
        </p>
        <ol v-else class="mt-3 flex flex-col gap-1.5">
            <li
                v-for="(entry, index) in highscores"
                :key="`${entry.stars}-${entry.seconds}`"
                class="flex items-center justify-between gap-2 rounded-lg py-1.5 pr-4 pl-3 text-sm"
                :class="
                    entry.names.includes(playerName)
                        ? 'bg-accent/10 font-semibold text-accent'
                        : 'text-slate-700 dark:text-slate-300'
                "
            >
                <span class="flex min-w-0 items-center gap-3">
                    <span
                        class="w-5 shrink-0 text-right font-mono text-xs text-slate-400"
                        >{{ startRank + index }}</span
                    >
                    <span class="truncate" :title="entry.names.join(', ')">{{
                        entry.names.join(', ')
                    }}</span>
                </span>
                <span
                    class="flex shrink-0 items-center gap-1 font-mono text-xs"
                >
                    {{ entry.stars }}
                    <svg
                        viewBox="0 0 24 24"
                        class="size-3.5 text-star"
                        fill="currentColor"
                    >
                        <path
                            d="M12 2l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.02l-5.88 3.09 1.12-6.55L2.48 8.92l6.58-.96L12 2z"
                        />
                    </svg>
                    <span class="text-slate-400 dark:text-slate-500">
                        {{ formatDuration(entry.seconds) }}
                    </span>
                </span>
            </li>
        </ol>
    </section>
</template>
