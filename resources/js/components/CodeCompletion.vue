<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import type { CodeBlank, CodeJudgement, CodeLevel } from '@/game/types';
import { code as codeRoute } from '@/routes/levels';

const props = defineProps<{ level: CodeLevel }>();

const emit = defineEmits<{ correct: [judgement: CodeJudgement] }>();

function isBlank(part: string | CodeBlank): part is CodeBlank {
    return typeof part !== 'string';
}

function shuffle<T>(items: T[]): T[] {
    const result = [...items];

    for (let i = result.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [result[i], result[j]] = [result[j], result[i]];
    }

    return result;
}

const blanks = computed(() => props.level.codeParts.filter(isBlank));

const shuffledOptions = new Map(
    blanks.value.map((blank) => [blank.id, shuffle(blank.options)]),
);

const filled = reactive<Record<string, string | null>>(
    Object.fromEntries(blanks.value.map((blank) => [blank.id, null])),
);

const activeBlankId = ref<string | null>(blanks.value[0]?.id ?? null);

const flashingWrong = reactive(new Set<string>());
const solved = ref(false);

const http = useHttp<{ answers: Record<string, string> }, CodeJudgement>({
    answers: {},
});

const activeOptions = computed(() =>
    activeBlankId.value ? (shuffledOptions.get(activeBlankId.value) ?? []) : [],
);

const allFilled = computed(() =>
    blanks.value.every((blank) => filled[blank.id] !== null),
);

function firstEmptyBlank(): string | null {
    return blanks.value.find((blank) => filled[blank.id] === null)?.id ?? null;
}

function selectBlank(blank: CodeBlank): void {
    if (solved.value) {
        return;
    }

    if (filled[blank.id] !== null) {
        filled[blank.id] = null;
    }

    activeBlankId.value = blank.id;
}

function pickOption(option: string): void {
    if (solved.value || !activeBlankId.value) {
        return;
    }

    filled[activeBlankId.value] = option;
    activeBlankId.value = firstEmptyBlank();
}

function run(): void {
    if (solved.value || !allFilled.value || http.processing) {
        return;
    }

    http.answers = Object.fromEntries(
        blanks.value.map((blank) => [blank.id, filled[blank.id] as string]),
    );

    http.post(codeRoute.url(props.level.id), {
        onSuccess: (judgement) => {
            if (judgement.correct) {
                solved.value = true;
                emit('correct', judgement);

                return;
            }

            for (const blankId of judgement.wrongBlanks) {
                flashingWrong.add(blankId);
            }

            setTimeout(() => {
                for (const blankId of judgement.wrongBlanks) {
                    filled[blankId] = null;
                    flashingWrong.delete(blankId);
                }

                activeBlankId.value = firstEmptyBlank();
            }, 550);
        },
    });
}
</script>

<template>
    <div class="mx-auto flex w-full max-w-2xl flex-col gap-4">
        <div
            class="overflow-x-auto rounded-xl border border-slate-200 bg-slate-50 shadow-inner dark:border-slate-700 dark:bg-slate-900"
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
        <template v-for="(part, index) in level.codeParts"><span v-if="typeof part === 'string'" :key="`s-${index}`">{{ part }}</span><button
                v-else
                :key="`b-${part.id}`"
                type="button"
                class="mx-0.5 inline-block min-w-16 rounded-md border px-2 align-baseline leading-6 transition"
                :class="{
                    'animate-shake border-danger bg-danger/10 text-danger': flashingWrong.has(part.id),
                    'border-success bg-success/10 text-success': solved,
                    'border-accent bg-accent/10': !solved && !flashingWrong.has(part.id) && activeBlankId === part.id,
                    'border-dashed border-slate-400 text-slate-400 dark:border-slate-500':
                        !solved && !flashingWrong.has(part.id) && activeBlankId !== part.id && filled[part.id] === null,
                    'border-slate-300 bg-white text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200':
                        !solved && !flashingWrong.has(part.id) && activeBlankId !== part.id && filled[part.id] !== null,
                }"
                @click="selectBlank(part)"
            >{{ filled[part.id] ?? '…' }}</button></template>
    }
}</pre>
        </div>

        <div
            v-if="!solved"
            class="flex flex-wrap items-center justify-center gap-2"
        >
            <button
                v-for="option in activeOptions"
                :key="option"
                type="button"
                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 font-mono text-sm text-slate-800 shadow-sm transition hover:border-accent hover:text-accent active:scale-[0.97] dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                @click="pickOption(option)"
            >
                {{ option }}
            </button>
            <span
                v-if="!activeBlankId && !allFilled"
                class="text-sm text-slate-400"
                >Select a blank to fill it.</span
            >
        </div>

        <button
            v-if="!solved"
            type="button"
            :disabled="!allFilled || http.processing"
            class="mx-auto rounded-xl bg-accent px-6 py-2.5 font-semibold text-white shadow-lg shadow-accent/25 transition hover:brightness-110 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
            @click="run"
        >
            Run ▸
        </button>
    </div>
</template>
