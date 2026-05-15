import ModCard from '@/Components/ModCard';
import PaginationLinks from '@/Components/PaginationLinks';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ModEntry, PageProps, Paginated } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { useTranslations } from '@/lib/translations';

function OwnerStatusNote({ mod }: { mod: ModEntry }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    if (!mod.rejection_reason && !mod.security_check?.result_summary) {
        return null;
    }

    return (
        <div className="mt-3 space-y-2">
            {mod.rejection_reason && (
                <p className="rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <span className="font-bold">{t('mods.rejection_reason', 'Rejection reason')}:</span> {mod.rejection_reason}
                </p>
            )}
            {mod.security_check?.result_summary && (
                <p className="rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <span className="font-bold">{t('security.heading', 'Security check')}:</span> {mod.security_check.result_summary}
                </p>
            )}
        </div>
    );
}

export default function Mine({ mods, flash }: PageProps<{ mods: Paginated<ModEntry> }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('mods.my_mods', 'My Mods')}</h2>}>
            <Head title={t('mods.my_mods', 'My Mods')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('mods.submitted_mods', 'Submitted mods')}</h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('mods.track_status_subtitle', 'Track pending, approved, and rejected entries.')}</p>
                        </div>
                        <Link href={route('mods.create')} className="rounded-md bg-indigo-600 px-4 py-2 font-semibold text-white hover:bg-indigo-500">
                            {t('mods.submit_mod', 'Submit Mod')}
                        </Link>
                    </div>

                    {flash.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}

                    {mods.data.length > 0 ? (
                        <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                            {mods.data.map((mod) => (
                                <div key={mod.id}>
                                    <ModCard mod={mod} />
                                    {mod.status === 'approved' && (
                                        <Link href={route('mods.versions.create', mod.slug)} className="mt-3 inline-flex rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                            {t('mods.submit_version', 'Submit new version')}
                                        </Link>
                                    )}
                                    <OwnerStatusNote mod={mod} />
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center dark:border-gray-700 dark:bg-gray-800">
                            <p className="text-gray-600 dark:text-gray-300">{t('mods.no_submitted_mods', 'You have not submitted any mods yet.')}</p>
                        </div>
                    )}

                    <div className="mt-8">
                        <PaginationLinks links={mods.links} />
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
