import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useTranslations } from '@/lib/translations';

type DashboardMetrics = {
    pending_mods: number;
    pending_reports: number;
    visible_comments: number;
    approved_mods: number;
    approved_mods_last_7_days: number;
    total_users?: number;
    new_users_last_7_days?: number;
};

type MetricCardProps = {
    label: string;
    value: number;
    href?: string;
    tone: 'cyan' | 'indigo' | 'amber' | 'emerald' | 'rose' | 'slate';
};

const toneClasses: Record<MetricCardProps['tone'], string> = {
    cyan: 'from-cyan-500/20 to-cyan-500/5 text-cyan-200 ring-cyan-300/20',
    indigo: 'from-indigo-500/20 to-indigo-500/5 text-indigo-200 ring-indigo-300/20',
    amber: 'from-amber-500/20 to-amber-500/5 text-amber-200 ring-amber-300/20',
    emerald: 'from-emerald-500/20 to-emerald-500/5 text-emerald-200 ring-emerald-300/20',
    rose: 'from-rose-500/20 to-rose-500/5 text-rose-200 ring-rose-300/20',
    slate: 'from-slate-500/20 to-slate-500/5 text-slate-200 ring-slate-300/20',
};

function MetricCard({ label, value, href, tone }: MetricCardProps) {
    const content = (
        <div className={`rounded-2xl bg-gradient-to-br p-5 ring-1 ${toneClasses[tone]}`}>
            <p className="text-sm font-semibold text-slate-300">{label}</p>
            <p className="mt-3 text-4xl font-black text-white">{value}</p>
        </div>
    );

    if (!href) {
        return content;
    }

    return (
        <Link href={href} className="block transition hover:-translate-y-0.5 hover:brightness-110">
            {content}
        </Link>
    );
}

export default function Dashboard({ metrics, canSeeUserMetrics }: PageProps<{ metrics: DashboardMetrics | null; canSeeUserMetrics: boolean }>) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    {t('dashboard.heading', 'Dashboard')}
                </h2>
            }
        >
            <Head title={t('dashboard.title', 'Dashboard')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <h1 className="text-2xl font-black text-gray-950 dark:text-white">{t('dashboard.welcome', 'Welcome back')}</h1>
                            <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{t('dashboard.logged_in', "You're logged in!")}</p>
                        </div>
                    </div>

                    {metrics && (
                        <section className="mt-8">
                            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                                <div>
                                    <h2 className="text-xl font-black text-gray-950 dark:text-white">{t('dashboard.metrics_heading', 'Editorial overview')}</h2>
                                    <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('dashboard.metrics_subtitle', 'Current moderation and community signals.')}</p>
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <MetricCard label={t('dashboard.pending_mods', 'Pending mods')} value={metrics.pending_mods} href={route('admin.moderation.index')} tone="amber" />
                                <MetricCard label={t('dashboard.pending_reports', 'Pending reports')} value={metrics.pending_reports} href={route('admin.reports.index')} tone="rose" />
                                <MetricCard label={t('dashboard.visible_comments', 'Visible comments')} value={metrics.visible_comments} tone="cyan" />
                                <MetricCard label={t('dashboard.approved_mods', 'Approved mods')} value={metrics.approved_mods} href={route('mods.index')} tone="emerald" />
                                <MetricCard label={t('dashboard.approved_mods_last_7_days', 'Approved in 7 days')} value={metrics.approved_mods_last_7_days} tone="indigo" />
                                {canSeeUserMetrics && typeof metrics.total_users === 'number' && (
                                    <MetricCard label={t('dashboard.total_users', 'Total users')} value={metrics.total_users} href={route('admin.users.index')} tone="slate" />
                                )}
                                {canSeeUserMetrics && typeof metrics.new_users_last_7_days === 'number' && (
                                    <MetricCard label={t('dashboard.new_users_last_7_days', 'New users in 7 days')} value={metrics.new_users_last_7_days} href={route('admin.users.index')} tone="cyan" />
                                )}
                            </div>
                        </section>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
