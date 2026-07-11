<script setup lang="ts">
import type { RenderedConnection } from '@/game/geometry';

defineProps<{
    width: number;
    height: number;
    connections: RenderedConnection[];
    livePath: string | null;
}>();

const strokeColors = {
    locked: 'stroke-fk',
    made: 'stroke-success',
    rejected: 'stroke-danger',
} as const;
</script>

<template>
    <svg
        class="pointer-events-none absolute inset-0 overflow-visible"
        :width="width"
        :height="height"
        :viewBox="`0 0 ${width} ${height}`"
        aria-hidden="true"
    >
        <path
            v-for="connection in connections"
            :key="connection.id"
            :d="connection.path"
            pathLength="1"
            fill="none"
            stroke-width="2.5"
            stroke-linecap="round"
            class="animate-line-draw"
            :class="strokeColors[connection.state]"
            :style="{
                strokeDasharray: 1,
                animationDelay: `${connection.delayMs}ms`,
            }"
        />
        <path
            v-if="livePath"
            :d="livePath"
            fill="none"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-dasharray="6 5"
            class="stroke-accent"
        />
    </svg>
</template>
