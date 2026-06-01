import PaginationLinks from '@/Components/PaginationLinks';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ModEntry, ModVersionEntry, PageProps, Paginated } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslations } from '@/lib/translations';
import SecurityCheckBadge from '@/Components/SecurityCheckBadge';

const statuses = ['pending', 'approved', 'rejected'] as const;

const securityDescriptionKeys: Record<string, string> = {
    missing: 'security.missing_description',
    not_submitted: 'security.not_submitted_description',
    pending: 'security.pending_description',
    clean: 'security.clean_description',
    suspicious: 'security.suspicious_description',
    failed: 'security.failed_description',
};

const securityBoxStyles: Record<string, string> = {
    missing: 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
    not_submitted: 'border-gray-200 bg-gray-50 text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300',
    pending: 'border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100',
    clean: 'border-green-200 bg-green-50 text-green-900 dark:border-green-900 dark:bg-green-950 dark:text-green-100',
    suspicious: 'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100',
    failed: 'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-100',
};

function SecurityReviewBox({ mod }: { mod: ModEntry }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const status = mod.security_check?.status ?? 'missing';
    const descriptionKey = securityDescriptionKeys[status] ?? securityDescriptionKeys.missing;
    const boxStyle = securityBoxStyles[status] ?? securityBoxStyles.missing;

    return (
        <div className={`mt-4 rounded-xl border p-4 text-sm ${boxStyle}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <SecurityCheckBadge securityCheck={mod.security_check} withLabel />
                {mod.security_check?.checked_at && (
                    <span className="text-xs font-semibold uppercase tracking-wide opacity-80">
                        {t('security.checked_at', 'Checked at')}: {new Date(mod.security_check.checked_at).toLocaleString()}
                    </span>
                )}
            </div>
            <p className="mt-3 leading-6">{t(descriptionKey, 'Security check status is available for editorial review.')}</p>
            {mod.security_check?.result_summary && <p className="mt-2 font-semibold">{mod.security_check.result_summary}</p>}
            <p className="mt-3 text-xs font-semibold uppercase tracking-wide opacity-80">
                {t('security.decision_support', 'Security checks are decision support only and do not approve mods automatically.')}
            </p>
        </div>
    );
}

function YouTubeReviewBox({ embedUrl, title }: { embedUrl: string; title: string }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [loaded, setLoaded] = useState(false);

    return (
        <div className="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-950 dark:border-indigo-900 dark:bg-indigo-950 dark:text-indigo-100">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <p className="font-bold">{t('mods.youtube_preview', 'YouTube preview')}</p>
                {!loaded && (
                    <button type="button" onClick={() => setLoaded(true)} className="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {t('mods.load_youtube_preview', 'Load YouTube preview')}
                    </button>
                )}
            </div>
            <p className="mt-2 text-xs font-semibold uppercase tracking-wide opacity-80">{t('mods.youtube_privacy_notice', 'YouTube will only load after you click because it is a third-party service.')}</p>
            {loaded && <iframe src={embedUrl} title={title} className="mt-3 aspect-video w-full rounded-lg border border-indigo-200 dark:border-indigo-800" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerPolicy="strict-origin-when-cross-origin" allowFullScreen />}
        </div>
    );
}

function AudioReviewBox({ version }: { version: ModVersionEntry }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);

    if (!version.audio_url) {
        return null;
    }

    return (
        <div className="mt-4 rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-950 dark:border-cyan-900 dark:bg-cyan-950 dark:text-cyan-100">
            <p className="font-bold">{t('mods.audio_preview', 'Audio preview')}</p>
            <audio controls preload="metadata" src={version.audio_url} className="mt-3 w-full" />
            {version.audio_original_name && <p className="mt-2 text-xs font-semibold uppercase tracking-wide opacity-80">{version.audio_original_name}</p>}
        </div>
    );
}

function RejectForm({ mod }: { mod: ModEntry }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm({
        rejection_reason: mod.rejection_reason ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.moderation.reject', mod.slug), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <button
                type="button"
                onClick={() => setOpen(true)}
                className="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950"
            >
                {t('actions.reject', 'Reject')}
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="space-y-2">
            <textarea
                value={data.rejection_reason}
                onChange={(event) => setData('rejection_reason', event.target.value)}
                rows={3}
                placeholder={t('admin.moderation.rejection_reason_placeholder', 'Explain why this mod was rejected')}
                className="w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            />
            {errors.rejection_reason && <p className="text-sm text-red-600">{errors.rejection_reason}</p>}
            <div className="flex gap-2">
                <button disabled={processing} className="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50">
                    {t('admin.moderation.confirm_reject', 'Confirm reject')}
                </button>
                <button type="button" onClick={() => setOpen(false)} className="rounded-md px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    {t('actions.cancel', 'Cancel')}
                </button>
            </div>
        </form>
    );
}

function VersionRejectForm({ version }: { version: ModVersionEntry }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [open, setOpen] = useState(false);
    const { data, setData, patch, processing, errors, reset } = useForm({
        rejection_reason: version.rejection_reason ?? '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.moderation.versions.reject', version.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setOpen(false);
            },
        });
    };

    if (!open) {
        return (
            <button type="button" onClick={() => setOpen(true)} className="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">
                {t('actions.reject', 'Reject')}
            </button>
        );
    }

    return (
        <form onSubmit={submit} className="space-y-2">
            <textarea value={data.rejection_reason} onChange={(event) => setData('rejection_reason', event.target.value)} rows={3} placeholder={t('admin.moderation.rejection_reason_placeholder', 'Explain why this mod was rejected')} className="w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
            {errors.rejection_reason && <p className="text-sm text-red-600">{errors.rejection_reason}</p>}
            <div className="flex gap-2">
                <button disabled={processing} className="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50">
                    {t('admin.moderation.confirm_reject', 'Confirm reject')}
                </button>
                <button type="button" onClick={() => setOpen(false)} className="rounded-md px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                    {t('actions.cancel', 'Cancel')}
                </button>
            </div>
        </form>
    );
}

function DeleteButtons({ mod }: { mod: ModEntry }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [showConfirm, setShowConfirm] = useState(false);
    const { data, setData, delete: destroy, processing, errors, reset } = useForm({
        confirmation: '',
    });

    const handleForceDelete = (event: FormEvent) => {
        event.preventDefault();
        destroy(route('admin.moderation.force-delete', mod.slug), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setShowConfirm(false);
            },
        });
    };

    if (showConfirm) {
        return (
            <form onSubmit={handleForceDelete} className="space-y-2">
                <p className="text-sm font-medium text-red-700 dark:text-red-300">{t('admin.moderation.confirm_permanent', 'This cannot be undone. All data will be lost.')}</p>
                <label className="block text-xs font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-400" htmlFor={`delete-confirmation-${mod.id}`}>
                    {t('admin.moderation.type_mod_title', 'Type the mod title to confirm')}
                </label>
                <input
                    id={`delete-confirmation-${mod.id}`}
                    value={data.confirmation}
                    onChange={(event) => setData('confirmation', event.target.value)}
                    className="w-full rounded-md border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                    placeholder={mod.title}
                />
                {errors.confirmation && <p className="text-sm text-red-600">{errors.confirmation}</p>}
                <div className="flex flex-col gap-2 sm:flex-row">
                    <button disabled={processing || data.confirmation !== mod.title} className="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50">
                        {t('admin.moderation.confirm_permanent_delete', 'Confirm permanent delete')}
                    </button>
                    <button type="button" onClick={() => { reset(); setShowConfirm(false); }} className="rounded-md px-3 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                        {t('actions.cancel', 'Cancel')}
                    </button>
                </div>
            </form>
        );
    }

    return (
        <button
            type="button"
            onClick={() => setShowConfirm(true)}
            className="w-full rounded-md border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700"
        >
            {t('actions.delete', 'Delete')}
        </button>
    );
}

export default function Index({ mods, modVersions, status, flash }: PageProps<{ mods: Paginated<ModEntry>; modVersions: Paginated<ModVersionEntry>; status: string }>) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const approve = (mod: ModEntry) => {
        router.patch(route('admin.moderation.approve', mod.slug), {}, { preserveScroll: true });
    };
    const approveVersion = (version: ModVersionEntry) => {
        router.patch(route('admin.moderation.versions.approve', version.id), {}, { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.moderation.title', 'Moderation Queue')}</h2>}>
            <Head title={t('admin.moderation.title', 'Moderation Queue')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.moderation.heading', 'Review submitted mods')}</h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.moderation.subtitle', 'Approve safe entries or reject submissions with a clear reason.')}</p>
                        </div>
                        <div className="flex gap-2">
                            {statuses.map((item) => (
                                <Link
                                    key={item}
                                    href={route('admin.moderation.index', { status: item })}
                                    className={
                                        'rounded-full px-4 py-2 text-sm font-semibold capitalize ' +
                                        (status === item
                                            ? 'bg-indigo-600 text-white'
                                            : 'bg-white text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200')
                                    }
                                >
                                    {t(`admin.moderation.${item}`, item)}
                                </Link>
                            ))}
                        </div>
                    </div>

                    {flash.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}

                    <div className="space-y-4">
                        {mods.data.map((mod) => (
                            <article key={mod.id} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                                <div className="grid gap-5 lg:grid-cols-[1fr_260px]">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            <span>{mod.category?.name}</span>
                                            <span>{t('mods.submitted_by', 'Submitted by')} {mod.user?.name}</span>
                                            <span className="capitalize">{mod.status}</span>
                                        </div>
                                        <h2 className="mt-2 text-xl font-bold text-gray-950 dark:text-white">{mod.title}</h2>
                                        <p className="mt-2 line-clamp-3 text-sm leading-6 text-gray-600 dark:text-gray-300">{mod.description}</p>
                                        <div className="mt-4 flex flex-wrap gap-3 text-sm">
                                            <Link href={route('mods.show', mod.slug)} className="font-semibold text-indigo-700 dark:text-indigo-300">
                                                {t('actions.preview', 'Preview')}
                                            </Link>
                                            {mod.external_download_url && (
                                                <a href={mod.external_download_url} target="_blank" rel="noreferrer" className="font-semibold text-indigo-700 dark:text-indigo-300">
                                                    {t('common.download_link', 'Download link')}
                                                </a>
                                            )}
                                            <span className="text-gray-600 dark:text-gray-300">
                                                {t('mods.download_clicks_count', '{count} download clicks').replace('{count}', String(mod.download_clicks_count ?? 0))}
                                            </span>
                                            {mod.virus_total_url && (
                                                <a href={mod.virus_total_url} target="_blank" rel="noreferrer" className="font-semibold text-indigo-700 dark:text-indigo-300">
                                                    {t('common.virustotal', 'VirusTotal')}
                                                </a>
                                            )}
                                        </div>
                                        <SecurityReviewBox mod={mod} />
                                        {mod.current_version && <AudioReviewBox version={mod.current_version} />}
                                        {mod.current_version?.youtube_embed_url && <YouTubeReviewBox embedUrl={mod.current_version.youtube_embed_url} title={`${mod.title} YouTube preview`} />}
                                    </div>

                                    <div className="space-y-3">
                                        {mod.status !== 'approved' && (
                                            <button onClick={() => approve(mod)} className="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500">
                                                {t('admin.moderation.approve_publish', 'Approve and publish')}
                                            </button>
                                        )}
                                        {mod.status !== 'rejected' && <RejectForm mod={mod} />}
                                        <DeleteButtons mod={mod} />
                                        {mod.rejection_reason && (
                                            <p className="rounded-md bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{mod.rejection_reason}</p>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>

                    {modVersions.data.length > 0 && (
                        <div className="mt-10">
                            <h2 className="text-xl font-bold text-gray-950 dark:text-white">{t('admin.moderation.version_heading', 'Review submitted versions')}</h2>
                            <div className="mt-4 space-y-4">
                                {modVersions.data.map((version) => (
                                    <article key={version.id} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                                        <div className="grid gap-5 lg:grid-cols-[1fr_260px]">
                                            <div>
                                                <div className="flex flex-wrap items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                                    <span>{version.mod?.title}</span>
                                                    <span>{t('mods.submitted_by', 'Submitted by')} {version.user?.name}</span>
                                                    <span className="capitalize">{version.status}</span>
                                                </div>
                                                <h3 className="mt-2 text-xl font-bold text-gray-950 dark:text-white">{t('mods.version', 'Version')} {version.version}</h3>
                                                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600 dark:text-gray-300">{version.changelog}</p>
                                                <div className="mt-4 flex flex-wrap gap-3 text-sm">
                                                    {version.mod && <Link href={route('mods.show', version.mod.slug)} className="font-semibold text-indigo-700 dark:text-indigo-300">{t('actions.preview', 'Preview')}</Link>}
                                                    {version.external_download_url && <a href={version.external_download_url} target="_blank" rel="noreferrer" className="font-semibold text-indigo-700 dark:text-indigo-300">{t('common.download_link', 'Download link')}</a>}
                                                    {version.virus_total_url && <a href={version.virus_total_url} target="_blank" rel="noreferrer" className="font-semibold text-indigo-700 dark:text-indigo-300">{t('common.virustotal', 'VirusTotal')}</a>}
                                                </div>
                                                <AudioReviewBox version={version} />
                                                {version.youtube_embed_url && <YouTubeReviewBox embedUrl={version.youtube_embed_url} title={`${version.mod?.title ?? 'Mod'} YouTube preview`} />}
                                            </div>
                                            <div className="space-y-3">
                                                {version.status !== 'approved' && (
                                                    <button onClick={() => approveVersion(version)} className="w-full rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white hover:bg-green-500">
                                                        {t('admin.moderation.approve_publish', 'Approve and publish')}
                                                    </button>
                                                )}
                                                {version.status !== 'rejected' && <VersionRejectForm version={version} />}
                                                {version.rejection_reason && <p className="rounded-md bg-red-50 p-3 text-sm text-red-800 dark:bg-red-950 dark:text-red-200">{version.rejection_reason}</p>}
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                            <div className="mt-8">
                                <PaginationLinks links={modVersions.links} />
                            </div>
                        </div>
                    )}

                    {mods.data.length === 0 && modVersions.data.length === 0 && (
                        <div className="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {t('admin.moderation.no_mods', 'No mods in this queue.')}
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
