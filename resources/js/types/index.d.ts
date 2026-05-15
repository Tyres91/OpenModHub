export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    blocked_at?: string | null;
    locale: string | null;
    roles: string[];
}

export interface PublicRank {
    id: number;
    name: string;
    required_published_mods: number;
    required_points: number;
    color: string;
    icon?: string | null;
    is_special: boolean;
}

export interface PublicUser {
    id: number;
    name: string;
    created_at?: string;
    published_mods_count: number;
    points: number;
    is_special_rank_locked: boolean;
    rank?: PublicRank | null;
}

export interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    is_active?: boolean;
    mods_count?: number;
    created_at?: string;
    updated_at?: string;
}

export interface Role {
    id: number;
    name: string;
    slug: string;
}

export interface ModImage {
    id: number;
    mod_id: number;
    url: string;
    alt_text?: string | null;
    sort_order: number;
}

export interface SecurityCheck {
    id: number;
    provider: 'virustotal';
    status: 'not_submitted' | 'pending' | 'clean' | 'suspicious' | 'failed';
    external_url?: string | null;
    analysis_id?: string | null;
    result_summary?: string | null;
    checked_at?: string | null;
    created_at: string;
    updated_at: string;
}

export interface ModVersionEntry {
    id: number;
    version: string;
    normalized_version?: string;
    changelog: string;
    external_download_url: string;
    virus_total_url?: string | null;
    download_clicks_count: number;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
    approved_at?: string | null;
    created_at: string;
    updated_at: string;
    is_current: boolean;
    security_check?: SecurityCheck | null;
    mod?: Pick<ModEntry, 'id' | 'title' | 'slug'>;
    user?: Pick<User, 'id' | 'name'> | null;
}

export interface ModEntry {
    id: number;
    title: string;
    slug: string;
    description: string;
    external_download_url: string;
    virus_total_url?: string | null;
    download_clicks_count: number;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason?: string | null;
    ratings_avg_score?: number | null;
    ratings_count?: number;
    approved_at?: string | null;
    created_at: string;
    updated_at: string;
    category?: Category;
    user?: PublicUser;
    images?: ModImage[];
    security_check?: SecurityCheck | null;
    current_version?: ModVersionEntry | null;
    versions?: ModVersionEntry[];
}

export interface CommentEntry {
    id: number;
    body: string;
    status: 'visible' | 'hidden';
    created_at: string;
    user: Pick<User, 'id' | 'name'>;
}

export interface Rank {
    id: number;
    name: string;
    required_published_mods: number;
    required_points: number;
    color: string;
    icon?: string | null;
    is_special: boolean;
    created_at?: string;
    updated_at?: string;
}

export interface RankPointRule {
    id: number;
    key: 'comment_created' | 'approved_mod' | 'approved_version' | 'download_threshold' | 'rating_received' | 'rating_average_bonus';
    label: string;
    points: number;
    threshold?: number | null;
    is_enabled: boolean;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User | null;
    };
    flash: {
        status?: string | null;
        error?: string | null;
        debugVerificationUrl?: string | null;
    };
    locale: string;
    defaultLocale: string;
    availableLocales: Record<string, string>;
    googleTagManagerId: string;
    branding: {
        logoUrl: string | null;
        logoText: string;
        showLogoText: boolean;
    };
    translations: Record<string, unknown>;
};
