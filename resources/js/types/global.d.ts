import type { Auth } from '@/types/auth';
import type { Team } from '@/types/teams';
import type { Release } from '@/types/update';

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            currentTeam: Team | null;
            teams: Team[];
            version: string;
            update: Release | null;
            [key: string]: unknown;
        };
    }
}

declare global {
    interface Window {
        Echo?: any;
        Pusher?: any;
    }
}
