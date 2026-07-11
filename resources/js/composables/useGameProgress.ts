import { computed, reactive, watch } from 'vue';

const STORAGE_KEY = 'eloquenter.v1';

export type Stars = 1 | 2 | 3;

interface LevelResult {
    stars: Stars;
    completedAt: string;
}

interface SaveData {
    version: 1;
    playerName: string;
    levels: Record<string, LevelResult>;
}

function defaultSave(): SaveData {
    return { version: 1, playerName: '', levels: {} };
}

function load(): SaveData {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return defaultSave();
        }

        const parsed = JSON.parse(raw) as SaveData;

        if (
            parsed.version !== 1 ||
            typeof parsed.playerName !== 'string' ||
            typeof parsed.levels !== 'object'
        ) {
            return defaultSave();
        }

        return parsed;
    } catch {
        return defaultSave();
    }
}

const state = reactive<SaveData>(load());

watch(
    state,
    () => {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch {
            // Storage may be unavailable (private mode); the game still works for the session.
        }
    },
    { deep: true },
);

export function useGameProgress() {
    return {
        playerName: computed(() => state.playerName),
        totalStars: computed(() =>
            Object.values(state.levels).reduce(
                (sum, level) => sum + level.stars,
                0,
            ),
        ),
        setPlayerName(name: string): void {
            state.playerName = name.trim();
        },
        starsFor(levelId: string): number {
            return state.levels[levelId]?.stars ?? 0;
        },
        isCompleted(levelId: string): boolean {
            return levelId in state.levels;
        },
        recordCompletion(levelId: string, stars: Stars): void {
            const previous = state.levels[levelId]?.stars ?? 0;

            state.levels[levelId] = {
                stars: Math.max(previous, stars) as Stars,
                completedAt: new Date().toISOString(),
            };
        },
        resetProgress(): void {
            state.playerName = '';
            state.levels = {};
        },
    };
}
