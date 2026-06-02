import ModCard from '@/Components/ModCard';
import PaginationLinks from '@/Components/PaginationLinks';
import PublicLayout from '@/Layouts/PublicLayout';
import { Category, ModEntry, PageProps, Paginated } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { FormEvent, useEffect, useRef, useState } from 'react';
import { useTranslations } from '@/lib/translations';

export default function Index({
    mods,
    categories,
    filters,
}: PageProps<{
    mods: Paginated<ModEntry>;
    categories: Category[];
    filters: { search: string; category: string; sort_by: string; sort_direction: string };
}>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [search, setSearch] = useState(filters.search ?? '');
    const [category, setCategory] = useState(filters.category ?? '');
    const [sortBy, setSortBy] = useState(filters.sort_by ?? 'approved_at');
    const [sortDirection, setSortDirection] = useState(filters.sort_direction ?? 'desc');
    const didMount = useRef(false);

    const applyFilters = (nextFilters: Partial<{ search: string; category: string; sort_by: string; sort_direction: string }> = {}) => {
        router.get(
            route('mods.index'),
            {
                search,
                category,
                sort_by: sortBy,
                sort_direction: sortDirection,
                ...nextFilters,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        applyFilters();
    };

    useEffect(() => {
        if (!didMount.current) {
            didMount.current = true;
            return;
        }

        const timeout = window.setTimeout(() => {
            applyFilters({ search });
        }, 400);

        return () => window.clearTimeout(timeout);
    }, [search]);

    return (
        <PublicLayout>
            <Head title={t('mods.title', 'Mods')} />

            <main className="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-10">
                <section>
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.35em] text-cyan-300">
                            {t('mods.title', 'Mods')}
                        </p>
                        <h1 className="mt-4 max-w-3xl text-3xl font-black tracking-tight sm:text-5xl lg:text-6xl">
                            {t('mods.discover', 'Discover approved mods from the community.')}
                        </h1>
                        <p className="mt-5 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg sm:leading-8">
                            {t('mods.discover_subtitle', 'Every public entry passed the OpenModHub moderation workflow before appearing here.')}
                        </p>
                    </div>

                    <form onSubmit={submit} className="mt-8 rounded-2xl border border-white/10 bg-white/5 p-4 shadow-2xl shadow-cyan-950/40">
                        <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-[1fr_180px_180px_160px_auto]">
                            <input
                                value={search}
                                onChange={(event) => setSearch(event.target.value)}
                                placeholder={t('mods.search_placeholder', 'Search mods')}
                                className="rounded-xl border-white/10 bg-slate-900 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                            />
                            <select
                                value={category}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setCategory(value);
                                    applyFilters({ category: value });
                                }}
                                className="rounded-xl border-white/10 bg-slate-900 text-white focus:border-cyan-400 focus:ring-cyan-400"
                            >
                                <option value="">{t('mods.all_categories', 'All categories')}</option>
                                {categories.map((item) => (
                                    <option key={item.id} value={item.slug}>
                                        {item.name}
                                    </option>
                                ))}
                            </select>
                            <select
                                value={sortBy}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setSortBy(value);
                                    applyFilters({ sort_by: value });
                                }}
                                className="rounded-xl border-white/10 bg-slate-900 text-white focus:border-cyan-400 focus:ring-cyan-400"
                                aria-label={t('mods.sort_by', 'Sort by')}
                            >
                                <option value="approved_at">{t('mods.sort_approved_at', 'Date')}</option>
                                <option value="title">{t('mods.sort_title', 'Title')}</option>
                                <option value="rating">{t('mods.sort_rating', 'Rating')}</option>
                                <option value="downloads">{t('mods.sort_downloads', 'Download clicks')}</option>
                            </select>
                            <select
                                value={sortDirection}
                                onChange={(event) => {
                                    const value = event.target.value;
                                    setSortDirection(value);
                                    applyFilters({ sort_direction: value });
                                }}
                                className="rounded-xl border-white/10 bg-slate-900 text-white focus:border-cyan-400 focus:ring-cyan-400"
                                aria-label={t('mods.sort_direction', 'Sort direction')}
                            >
                                <option value="desc">{t('mods.sort_desc', 'Descending')}</option>
                                <option value="asc">{t('mods.sort_asc', 'Ascending')}</option>
                            </select>
                            <button className="rounded-xl bg-cyan-400 px-5 py-2 font-bold text-slate-950 hover:bg-cyan-300 md:col-span-2 xl:col-span-1">
                                {t('actions.filter', 'Filter')}
                            </button>
                        </div>
                    </form>
                </section>

                <section className="mt-10">
                    {mods.data.length > 0 ? (
                        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            {mods.data.map((mod) => (
                                <ModCard key={mod.id} mod={mod} />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed border-white/20 p-10 text-center text-slate-300">
                            {t('mods.no_results', 'No approved mods match your filters yet.')}
                        </div>
                    )}
                </section>

                <div className="mt-8">
                    <PaginationLinks links={mods.links} />
                </div>
            </main>
        </PublicLayout>
    );
}
