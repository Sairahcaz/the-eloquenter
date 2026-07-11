import type { InjectionKey } from 'vue';
import type { AnchorSide } from '@/game/geometry';
import type { ColumnRef } from '@/game/types';

export type DotStatus = 'idle' | 'connected' | 'dragging';

/**
 * Contract between the GameBoard and the column dots nested inside its table
 * cards, provided/injected to avoid prop-drilling through TableCard.
 */
export interface BoardApi {
    isInteractive(): boolean;
    allColumnsConnectable(): boolean;
    registerDot(ref: ColumnRef, el: HTMLElement): void;
    unregisterDot(ref: ColumnRef): void;
    dotSide(ref: ColumnRef): AnchorSide;
    dotStatus(ref: ColumnRef): DotStatus;
    startDrag(ref: ColumnRef, event: PointerEvent): void;
}

export const boardApiKey: InjectionKey<BoardApi> = Symbol('boardApi');
