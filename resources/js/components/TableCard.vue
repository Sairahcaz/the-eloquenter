<script setup lang="ts">
import { ref, watch } from 'vue';
import ColumnRow from '@/components/ColumnRow.vue';
import type { TableCardDef } from '@/game/types';

const props = defineProps<{
    table: TableCardDef;
    shakeSignal?: number;
}>();

const shaking = ref(false);

watch(
    () => props.shakeSignal,
    () => {
        shaking.value = true;
        setTimeout(() => (shaking.value = false), 450);
    },
);
</script>

<template>
    <div
        class="w-52 rounded-xl border bg-white shadow-md sm:w-56 dark:bg-slate-900"
        :class="[
            shaking
                ? 'animate-shake border-danger'
                : table.pivot
                  ? 'border-morph/40'
                  : 'border-slate-200 dark:border-slate-700',
        ]"
    >
        <div
            class="flex items-center justify-between rounded-t-xl border-b px-3 py-2"
            :class="
                table.pivot
                    ? 'border-morph/30 bg-morph/10'
                    : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/60'
            "
        >
            <span
                class="flex items-center gap-2 font-mono text-sm font-semibold text-slate-900 dark:text-white"
            >
                <svg
                    viewBox="0 0 24 24"
                    class="size-3.5 text-slate-400"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                >
                    <ellipse cx="12" cy="5" rx="8" ry="3" />
                    <path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5" />
                    <path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3" />
                </svg>
                {{ table.name }}
            </span>
            <span
                v-if="table.pivot"
                class="rounded bg-morph/20 px-1.5 py-0.5 text-[9px] font-bold tracking-wide text-morph uppercase"
            >
                pivot
            </span>
        </div>
        <ul class="divide-y divide-slate-100 py-1 dark:divide-slate-800">
            <ColumnRow
                v-for="column in table.columns"
                :key="column.name"
                :table-id="table.id"
                :column="column"
            />
        </ul>
    </div>
</template>
