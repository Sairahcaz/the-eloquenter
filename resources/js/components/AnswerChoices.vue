<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import type { GuessJudgement, RelationType } from '@/game/types';
import { guess as guessRoute } from '@/routes/levels';

const props = defineProps<{
    levelId: string;
    perspective: string;
    choices: RelationType[];
}>();

const emit = defineEmits<{ correct: [judgement: GuessJudgement] }>();

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
const solvedChoice = ref<RelationType | null>(null);

const http = useHttp<{ choice: RelationType | null }, GuessJudgement>({
    choice: null,
});

function pick(choice: RelationType): void {
    if (solvedChoice.value || wrongPicks.has(choice) || http.processing) {
        return;
    }

    http.choice = choice;
    http.post(guessRoute.url(props.levelId), {
        onSuccess: (judgement) => {
            if (judgement.correct) {
                solvedChoice.value = choice;
                emit('correct', judgement);

                return;
            }

            wrongPicks.add(choice);
        },
    });
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
                :disabled="solvedChoice !== null || wrongPicks.has(choice)"
                class="rounded-xl border px-3 py-3.5 font-mono text-base font-semibold transition active:scale-[0.98] sm:px-4 sm:py-3 sm:text-sm"
                :class="{
                    'animate-shake border-danger bg-danger/10 text-danger':
                        wrongPicks.has(choice),
                    'border-success bg-success/10 text-success':
                        solvedChoice === choice,
                    'border-slate-200 bg-white text-slate-800 hover:border-accent hover:text-accent dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200':
                        !wrongPicks.has(choice) && solvedChoice !== choice,
                }"
                @click="pick(choice)"
            >
                {{ choice }}
            </button>
        </div>
    </div>
</template>
