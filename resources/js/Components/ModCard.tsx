import { ModEntry, PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import RankBadge from '@/Components/RankBadge';
import SecurityCheckBadge from '@/Components/SecurityCheckBadge';
import { useTranslations } from '@/lib/translations';

const statusMap: Record<string, string> = {
    pending: 'mods.status_pending',
    approved: 'mods.status_approved',
    rejected: 'mods.status_rejected',
};

export default function ModCard({ mod }: { mod: ModEntry }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const image = mod.images?.[0];
    const statusKey = statusMap[mod.status] ?? 'mods.status_pending';

    return (
        <article className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-800 dark:bg-gray-900">
            <Link href={route('mods.show', mod.slug)}>
                <div className="aspect-video bg-gradient-to-br from-indigo-950 via-slate-900 to-cyan-950">
                    {image ? (
                        <img
                            src={image.url}
                            alt={image.alt_text ?? `${mod.title} screenshot`}
                            className="h-full w-full object-cover"
                            loading="lazy"
                        />
                    ) : (
                        <div className="flex h-full items-center justify-center px-6 text-center text-sm font-semibold uppercase tracking-[0.3em] text-cyan-100/70">
                            OpenModHub
                        </div>
                    )}
                </div>
            </Link>

            <div className="space-y-4 p-5">
                <div className="flex items-center justify-between gap-3">
                    <span className="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
                        {mod.category?.name ?? t('common.uncategorized', 'Uncategorized')}
                    </span>
                    <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {t(statusKey, mod.status)}
                    </span>
                </div>

                <div>
                    <SecurityCheckBadge securityCheck={mod.security_check} />
                </div>

                <div>
                    <Link
                        href={route('mods.show', mod.slug)}
                        className="text-xl font-bold text-gray-950 hover:text-indigo-700 dark:text-white dark:hover:text-indigo-300"
                    >
                        {mod.title}
                    </Link>
                    <p className="mt-2 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        {mod.description}
                    </p>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-gray-500 dark:text-gray-400">
                    <span>
                        {t('mods.submitted_by', 'Submitted by')}{' '}
                        {mod.user ? (
                            <Link href={route('users.show', mod.user.id)} className="font-semibold text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200">
                                {mod.user.name}
                            </Link>
                        ) : t('mods.unknown', 'Unknown')}
                    </span>
                    <RankBadge rank={mod.user?.rank} compact />
                    <span>{mod.ratings_avg_score ? `${mod.ratings_avg_score}/5` : t('mods.no_ratings', 'No ratings yet')}</span>
                    <span>{t('mods.download_clicks_count', '{count} download clicks').replace('{count}', String(mod.download_clicks_count ?? 0))}</span>
                    <Link
                        href={route('mods.show', mod.slug)}
                        className="font-semibold text-indigo-700 hover:text-indigo-900 dark:text-indigo-300 dark:hover:text-indigo-200"
                    >
                        {t('actions.view_details', 'View details')}
                    </Link>
                </div>
            </div>
        </article>
    );
}
