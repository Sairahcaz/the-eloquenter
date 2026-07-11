<script setup lang="ts">
import { computed } from 'vue';
import { shareOnXUrl } from '@/game/share';

const props = defineProps<{ text: string }>();

const href = computed(() => shareOnXUrl(props.text, window.location.origin));
</script>

<template>
    <a
        :href="href"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 shadow-sm transition hover:border-slate-500 active:scale-[0.98] dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-slate-400"
    >
        <svg viewBox="0 0 24 24" class="size-3.5" fill="currentColor">
            <path
                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
            />
        </svg>
        Share on X
    </a>
</template>
