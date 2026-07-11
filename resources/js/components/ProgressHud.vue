<script setup lang="ts">
defineProps<{
    playerName: string;
    totalStars: number;
    maxStars: number;
}>();
</script>

<template>
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="text-sm text-slate-500 dark:text-slate-400">Agent</p>
            <p class="text-lg font-semibold text-slate-900 dark:text-white">
                {{ playerName }}
            </p>
        </div>
        <div
            class="flex items-center gap-2 rounded-full border border-slate-200 bg-white px-4 py-2 dark:border-slate-700 dark:bg-slate-900"
        >
            <svg
                viewBox="0 0 24 24"
                class="size-5 text-star"
                fill="currentColor"
            >
                <path
                    d="M12 2l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.02l-5.88 3.09 1.12-6.55L2.48 8.92l6.58-.96L12 2z"
                />
            </svg>
            <span
                class="font-mono text-sm font-semibold text-slate-900 dark:text-white"
                >{{ totalStars }}/{{ maxStars }}</span
            >
        </div>
    </div>
</template>
