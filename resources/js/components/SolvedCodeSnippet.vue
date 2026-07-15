<script setup lang="ts">
import type { CodeLevel } from '@/game/types';

defineProps<{
    level: CodeLevel;
    answers: Record<string, string>;
}>();
</script>

<template>
    <div
        class="w-full overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 shadow-inner dark:border-slate-700 dark:bg-slate-900"
    >
        <div
            class="flex items-center gap-1.5 border-b border-slate-200 px-4 py-2.5 dark:border-slate-700"
        >
            <span class="size-2.5 rounded-full bg-danger/60" />
            <span class="size-2.5 rounded-full bg-star/60" />
            <span class="size-2.5 rounded-full bg-success/60" />
            <span class="ml-2 font-mono text-xs text-slate-400"
                >app/Models/{{ level.model }}.php</span
            >
        </div>
        <pre
            class="px-4 py-3 font-mono text-sm leading-7 text-slate-800 dark:text-slate-200"
        ><span class="text-morph">class</span> {{ level.model }} <span class="text-morph">extends</span> Model
{
    <span class="text-morph">public function</span> <span class="text-fk">{{ level.method }}</span>()
    {
        <template v-for="(part, index) in level.codeParts"><span v-if="typeof part === 'string'" :key="`s-${index}`">{{ part }}</span><span
                v-else
                :key="`b-${part.id}`"
                class="mx-0.5 inline-block rounded-md border border-success bg-success/10 px-2 align-baseline leading-6 text-success"
            >{{ answers[part.id] }}</span></template>
    }
}</pre>
    </div>
</template>
