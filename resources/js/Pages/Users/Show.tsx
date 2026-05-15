import ModCard from '@/Components/ModCard';
import PaginationLinks from '@/Components/PaginationLinks';
import RankBadge from '@/Components/RankBadge';
import PublicLayout from '@/Layouts/PublicLayout';
import { ModEntry, PageProps, Paginated, PublicUser } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useTranslations } from '@/lib/translations';

export default function Show({ profileUser, mods }: PageProps<{ profileUser: PublicUser; mods: Paginated<ModEntry> }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    return (
        <PublicLayout>
            <Head title={profileUser.name} />

            <main className="mx-auto max-w-6xl px-6 py-10">
                <section className="overflow-hidden rounded-3xl border border-white/10 bg-gradient-to-br from-slate-950 via-indigo-950 to-cyan-950 p-8 shadow-2xl">
                    <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p className="text-sm font-bold uppercase tracking-[0.35em] text-cyan-200/80">{t('users.profile', 'User profile')}</p>
                            <h1 className="mt-3 text-4xl font-black tracking-tight text-white sm:text-5xl">{profileUser.name}</h1>
                            <p className="mt-3 text-sm text-slate-300">
                                {t('users.published_mods_count', '{count} published mods').replace('{count}', String(profileUser.published_mods_count))}
                            </p>
                            <p className="mt-1 text-sm font-semibold text-cyan-200">
                                {t('users.points_count', '{count} points').replace('{count}', String(profileUser.points))}
                            </p>
                        </div>

                        <div className="rounded-2xl border border-white/10 bg-white/10 p-5 text-white">
                            <p className="mb-3 text-xs font-bold uppercase tracking-[0.25em] text-slate-300">{t('users.current_rank', 'Current rank')}</p>
                            {profileUser.rank ? (
                                <div className="space-y-3">
                                    <RankBadge rank={profileUser.rank} />
                                    {profileUser.is_special_rank_locked && (
                                        <p className="text-xs font-semibold uppercase tracking-wide text-cyan-200">{t('users.special_rank', 'Special rank')}</p>
                                    )}
                                </div>
                            ) : (
                                <p className="text-sm text-slate-300">{t('users.no_rank_yet', 'No rank yet')}</p>
                            )}
                        </div>
                    </div>
                </section>

                <section className="mt-10">
                    <div className="mb-5 flex items-center justify-between gap-4">
                        <h2 className="text-2xl font-black text-white">{t('users.published_mods', 'Published mods')}</h2>
                    </div>

                    {mods.data.length > 0 ? (
                        <>
                            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                                {mods.data.map((mod) => <ModCard key={mod.id} mod={mod} />)}
                            </div>
                            <PaginationLinks links={mods.links} />
                        </>
                    ) : (
                        <div className="rounded-2xl border border-white/10 bg-white/5 p-8 text-center text-slate-300">
                            {t('users.no_published_mods', 'This user has no published mods yet.')}
                        </div>
                    )}
                </section>
            </main>
        </PublicLayout>
    );
}
