import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { PlayerIdentity } from '@/game/types';

const STORAGE_KEY = 'eloquenter.v2';

export type Stars = 1 | 2 | 3;

interface ProgressPageProps {
    player: PlayerIdentity | null;
    completions: Record<string, number>;
    [key: string]: unknown;
}

/**
 * Progress lives on the server and arrives as Inertia props; localStorage
 * only keeps the player token so returning players can resume their session.
 */
export function useGameProgress() {
    const page = usePage<ProgressPageProps>();

    const completions = computed(() => page.props.completions ?? {});

    return {
        playerName: computed(() => page.props.player?.name ?? ''),
        totalStars: computed(() =>
            Object.values(completions.value).reduce(
                (sum, stars) => sum + stars,
                0,
            ),
        ),
        starsFor(levelId: string): number {
            return completions.value[levelId] ?? 0;
        },
        isCompleted(levelId: string): boolean {
            return levelId in completions.value;
        },
    };
}

export function storedIdentity(): PlayerIdentity | null {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
            return null;
        }

        const parsed = JSON.parse(raw) as PlayerIdentity;

        return typeof parsed.name === 'string' &&
            typeof parsed.token === 'string'
            ? parsed
            : null;
    } catch {
        return null;
    }
}

export function rememberIdentity(identity: PlayerIdentity): void {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(identity));
    } catch {
        // Storage may be unavailable (private mode); resuming just won't work.
    }
}

export function forgetIdentity(): void {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch {
        // Nothing to forget if storage is unavailable.
    }
}
