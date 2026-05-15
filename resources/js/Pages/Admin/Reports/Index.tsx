import PaginationLinks from '@/Components/PaginationLinks';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Paginated } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useTranslations } from '@/lib/translations';

interface ReportEntry {
    id: number;
    reason: string;
    message: string | null;
    status: 'pending' | 'resolved' | 'dismissed';
    created_at: string;
    user: { id: number; name: string };
    mod: { id: number; title: string; slug: string };
    reviewer: { id: number; name: string } | null;
}

const statusColors: Record<string, string> = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    resolved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    dismissed: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200',
};

export default function Index({ reports, flash }: PageProps<{ reports: Paginated<ReportEntry> }>) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);

    const reasonLabels: Record<string, string> = {
        broken_link: t('mods.reason_broken_link', 'Broken download link'),
        malware: t('mods.reason_malware', 'Malware or suspicious content'),
        spam: t('mods.reason_spam', 'Spam or misleading'),
        other: t('mods.reason_other', 'Other'),
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.reports.title', 'Report Management')}</h2>}>
            <Head title={t('admin.reports.title', 'Report Management')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.reports.heading', 'Community reports')}</h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.reports.subtitle', 'Review user reports and resolve or dismiss them.')}</p>
                    </div>

                    {flash.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}

                    <div className="space-y-4">
                        {reports.data.map((report) => (
                            <article key={report.id} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                                <div className="grid gap-5 lg:grid-cols-[1fr_200px]">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            <span className={`rounded-full px-2 py-1 ${statusColors[report.status]}`}>{report.status}</span>
                                            <span>{reasonLabels[report.reason] ?? report.reason}</span>
                                            <span>{t('mods.submitted_by', 'Submitted by')} {report.user.name}</span>
                                            <span>{report.created_at}</span>
                                        </div>
                                        <h2 className="mt-2 text-lg font-bold text-gray-950 dark:text-white">
                                            <Link href={route('mods.show', report.mod.slug)} className="hover:underline">
                                                {report.mod.title}
                                            </Link>
                                        </h2>
                                        {report.message && <p className="mt-2 text-sm leading-6 text-gray-600 dark:text-gray-300">{report.message}</p>}
                                        {report.reviewer && (
                                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('common.reviewed_by', 'Reviewed by')} {report.reviewer.name}</p>
                                        )}
                                    </div>

                                    <div className="space-y-3">
                                        {report.status === 'pending' && (
                                            <>
                                                <button onClick={() => router.patch(route('admin.reports.resolve', report.id), {}, { preserveScroll: true })} className="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500">
                                                    {t('actions.resolve', 'Resolve')}
                                                </button>
                                                <button onClick={() => router.patch(route('admin.reports.dismiss', report.id), {}, { preserveScroll: true })} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                                    {t('actions.dismiss', 'Dismiss')}
                                                </button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>

                    {reports.data.length === 0 && (
                        <div className="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {t('admin.reports.no_reports', 'No reports to review.')}
                        </div>
                    )}

                    <div className="mt-8">
                        <PaginationLinks links={reports.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
