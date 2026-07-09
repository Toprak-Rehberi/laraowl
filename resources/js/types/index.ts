import type { Auth } from './auth';
import type { Team } from './teams';
import type { Release } from './update';

export type * from './auth';
export type * from './navigation';
export type * from './teams';
export type * from './ui';
export type * from './update';

export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    currentTeam: Team | null;
    teams: Team[];
    version: string;
    update: Release | null;
    [key: string]: unknown;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & SharedData;
