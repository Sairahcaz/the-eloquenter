<script setup lang="ts">
import { reactive, ref } from 'vue';
import type { RelationType } from '@/game/types';

const props = defineProps<{
    perspective: string;
    choices: RelationType[];
    answer: RelationType;
}>();

const emit = defineEmits<{ correct: []; mistake: [] }>();

function shuffle<T>(items: T[]): T[] {
    const result = [...items];

    for (let i = result.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [result[i], result[j]] = [result[j], result[i]];
    }

    return result;
}

const shuffledChoices = ref(shuffle(props.choices));
const wrongPicks = reactive(new Set<RelationType>());
const solved = ref(false);

function pick(choice: RelationType): void {
    if (solved.value || wrongPicks.has(choice)) {
        return;
    }

    if (choice === props.answer) {
        solved.value = true;
        emit('correct');

        return;
    }

    wrongPicks.add(choice);
    emit('mistake');
}
</script>

<template>
    <div class="flex flex-col items-center gap-4">
        <p class="text-center font-medium text-slate-700 dark:text-slate-300">
            {{ perspective }}
        </p>
        <div class="grid w-full max-w-lg grid-cols-2 gap-3">
            <button
                v-for="choice in shuffledChoices"
                :key="choice"
                type="button"
                :disabled="solved || wrongPicks.has(choice)"
                class="rounded-xl border px-4 py-3 font-mono text-sm font-semibold transition active:scale-[0.98]"
                :class="{
                    'animate-shake border-danger bg-danger/10 text-danger':
                        wrongPicks.has(choice),
                    'border-success bg-success/10 text-success':
                        solved && choice === answer,
                    'border-slate-200 bg-white text-slate-800 hover:border-accent hover:text-accent dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200':
                        !wrongPicks.has(choice) &&
                        !(solved && choice === answer),
                }"
                @click="pick(choice)"
            >
                {{ choice }}
            </button>
        </div>
    </div>
</template>
