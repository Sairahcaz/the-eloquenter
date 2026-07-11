<script setup lang="ts">
withDefaults(
    defineProps<{
        stars: number;
        animated?: boolean;
        size?: 'sm' | 'lg';
    }>(),
    { animated: false, size: 'sm' },
);
</script>

<template>
    <div
        class="flex items-center justify-center"
        :class="size === 'lg' ? 'gap-2' : 'gap-0.5'"
    >
        <svg
            v-for="index in 3"
            :key="index"
            viewBox="0 0 24 24"
            :class="[
                size === 'lg' ? 'size-12' : 'size-3.5',
                index <= stars
                    ? 'text-star'
                    : 'text-slate-300 dark:text-slate-700',
                animated && index <= stars ? 'animate-star-pop' : '',
            ]"
            :style="
                animated && index <= stars
                    ? { animationDelay: `${index * 180}ms` }
                    : undefined
            "
            fill="currentColor"
        >
            <path
                d="M12 2l2.94 5.96 6.58.96-4.76 4.64 1.12 6.55L12 17.02l-5.88 3.09 1.12-6.55L2.48 8.92l6.58-.96L12 2z"
            />
        </svg>
    </div>
</template>
