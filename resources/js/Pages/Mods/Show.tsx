import { CommentEntry, ModEntry, PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import PublicLayout from '@/Layouts/PublicLayout';
import RankBadge from '@/Components/RankBadge';
import SecurityCheckBadge from '@/Components/SecurityCheckBadge';
import { useTranslations } from '@/lib/translations';

const securityDescriptionKeys: Record<string, string> = {
    missing: 'security.missing_description',
    not_submitted: 'security.not_submitted_description',
    pending: 'security.pending_description',
    clean: 'security.clean_description',
    suspicious: 'security.suspicious_description',
    failed: 'security.failed_description',
};

const securityPanelStyles: Record<string, string> = {
    missing: 'border-white/10 bg-white/5 text-slate-300',
    not_submitted: 'border-white/10 bg-white/5 text-slate-300',
    pending: 'border-amber-400/30 bg-amber-400/10 text-amber-100',
    clean: 'border-green-400/30 bg-green-400/10 text-green-100',
    suspicious: 'border-red-400/40 bg-red-400/10 text-red-100',
    failed: 'border-red-400/40 bg-red-400/10 text-red-100',
};

function YouTubePreview({ embedUrl, title }: { embedUrl: string; title: string }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [loaded, setLoaded] = useState(false);

    if (loaded) {
        return (
            <iframe
                src={embedUrl}
                title={title}
                className="aspect-video w-full rounded-2xl border border-white/10"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerPolicy="strict-origin-when-cross-origin"
                allowFullScreen
            />
        );
    }

    return (
        <div className="flex aspect-video flex-col items-center justify-center rounded-2xl border border-white/10 bg-slate-950 p-6 text-center">
            <p className="text-sm leading-6 text-slate-300">{t('mods.youtube_privacy_notice', 'YouTube will only load after you click because it is a third-party service.')}</p>
            <button type="button" onClick={() => setLoaded(true)} className="mt-4 rounded-xl bg-cyan-400 px-5 py-3 font-black text-slate-950 hover:bg-cyan-300">
                {t('mods.load_youtube_preview', 'Load YouTube preview')}
            </button>
        </div>
    );
}

export default function Show({
    mod,
    comments,
    userRating,
    canModerateComments,
    flash,
}: PageProps<{
    mod: ModEntry;
    comments: CommentEntry[];
    userRating: number | null;
    canModerateComments: boolean;
}>) {
    const { auth, translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const heroImage = mod.images?.[0];
    const securityStatus = mod.security_check?.status ?? 'missing';
    const securityDescriptionKey = securityDescriptionKeys[securityStatus] ?? securityDescriptionKeys.missing;
    const securityPanelStyle = securityPanelStyles[securityStatus] ?? securityPanelStyles.missing;
    const ratingForm = useForm({ score: String(userRating ?? '') });
    const commentForm = useForm({ body: '' });
    const reportForm = useForm({ reason: '', message: '' });

    const submitRating = (event: FormEvent) => {
        event.preventDefault();
        ratingForm.post(route('mods.ratings.store', mod.slug), { preserveScroll: true });
    };

    const submitComment = (event: FormEvent) => {
        event.preventDefault();
        commentForm.post(route('mods.comments.store', mod.slug), {
            preserveScroll: true,
            onSuccess: () => commentForm.reset(),
        });
    };

    const submitReport = (event: FormEvent) => {
        event.preventDefault();
        reportForm.post(route('mods.reports.store', mod.slug), {
            preserveScroll: true,
            onSuccess: () => reportForm.reset(),
        });
    };

    return (
        <PublicLayout>
            <Head title={mod.title} />

            <main className="mx-auto max-w-6xl px-6 py-8">
                <Link href={route('mods.index')} className="text-sm font-semibold text-cyan-300 hover:text-cyan-200">
                    {t('actions.back', 'Back')} {t('mods.title', 'Mods').toLowerCase()}
                </Link>

                <div className="mt-6 overflow-hidden rounded-3xl border border-white/10 bg-white/5">
                    <div className="aspect-[16/7] bg-gradient-to-br from-indigo-950 via-slate-900 to-cyan-950">
                        {heroImage && (
                            <img src={heroImage.url} alt={heroImage.alt_text ?? `${mod.title} screenshot`} className="h-full w-full object-cover" />
                        )}
                    </div>

                    <div className="grid gap-8 p-6 lg:grid-cols-[1fr_320px] lg:p-8">
                        <section>
                            <div className="flex flex-wrap gap-3">
                                <span className="rounded-full bg-cyan-400/10 px-3 py-1 text-xs font-bold uppercase text-cyan-200">
                                    {mod.category?.name}
                                </span>
                                <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-bold uppercase text-white/80">
                                    {mod.status}
                                </span>
                            </div>

                            <h1 className="mt-5 text-4xl font-black tracking-tight sm:text-5xl">{mod.title}</h1>
                            <div className="mt-3 flex flex-wrap items-center gap-3 text-sm text-slate-400">
                                <span>
                                    {t('mods.submitted_by', 'Submitted by')}{' '}
                                    {mod.user ? (
                                        <Link href={route('users.show', mod.user.id)} className="font-semibold text-cyan-200 hover:text-cyan-100">
                                            {mod.user.name}
                                        </Link>
                                    ) : t('mods.unknown', 'Unknown')}
                                </span>
                                <RankBadge rank={mod.user?.rank} />
                            </div>
                            <p className="mt-3 text-sm font-semibold text-cyan-200">
                                {t('mods.rating', 'Rating')}: {mod.ratings_avg_score ? `${mod.ratings_avg_score}/5` : t('mods.no_ratings', 'No ratings yet')} ({mod.ratings_count ?? 0})
                            </p>
                            {mod.current_version && (
                                <p className="mt-2 text-sm font-semibold text-slate-300">
                                    {t('mods.current_version', 'Current version')}: <span className="text-cyan-200">{mod.current_version.version}</span>
                                </p>
                            )}

                            <div className="prose prose-invert mt-8 max-w-none whitespace-pre-line text-slate-200">
                                {mod.description}
                            </div>
                            {mod.current_version?.changelog && (
                                <div className="mt-8 rounded-2xl border border-white/10 bg-slate-950 p-5">
                                    <h2 className="text-xl font-bold text-white">{t('mods.current_changelog', 'Current changelog')}</h2>
                                    <p className="mt-3 whitespace-pre-line text-sm leading-6 text-slate-200">{mod.current_version.changelog}</p>
                                </div>
                            )}
                            {mod.current_version?.youtube_embed_url && (
                                <div className="mt-8">
                                    <h2 className="mb-3 text-xl font-bold text-white">{t('mods.youtube_preview', 'YouTube preview')}</h2>
                                    <YouTubePreview embedUrl={mod.current_version.youtube_embed_url} title={`${mod.title} YouTube preview`} />
                                </div>
                            )}
                        </section>

                        <aside className="space-y-4 rounded-2xl border border-white/10 bg-slate-950 p-5">
                            <a
                                href={route('mods.download', mod.slug)}
                                target="_blank"
                                rel="noreferrer"
                                className="block rounded-xl bg-cyan-400 px-5 py-3 text-center font-black text-slate-950 hover:bg-cyan-300"
                            >
                                {t('mods.download', 'External Download')}
                            </a>
                            <p className="rounded-xl border border-white/10 bg-white/5 p-4 text-center text-sm font-semibold text-slate-300">
                                {t('mods.download_clicks_count', '{count} download clicks').replace('{count}', String(mod.download_clicks_count ?? 0))}
                            </p>

                            {mod.virus_total_url ? (
                                <a
                                    href={mod.virus_total_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="block rounded-xl border border-white/10 px-5 py-3 text-center font-bold text-cyan-200 hover:border-cyan-300"
                                >
                                    {t('mods.virustotal', 'VirusTotal Report')}
                                </a>
                            ) : (
                                <p className="rounded-xl border border-amber-400/20 bg-amber-400/10 p-4 text-sm text-amber-100">
                                    {t('mods.no_virustotal', 'No VirusTotal link was provided for this entry.')}
                                </p>
                            )}

                            <div className={`rounded-xl border p-4 ${securityPanelStyle}`}>
                                <p className="text-xs font-bold uppercase tracking-wide text-slate-400">{t('security.heading', 'Security check')}</p>
                                <div className="mt-3">
                                    <SecurityCheckBadge securityCheck={mod.security_check} />
                                </div>
                                <p className="mt-3 text-sm leading-6">{t(securityDescriptionKey, 'Security check status is available for review.')}</p>
                                {mod.security_check?.result_summary && (
                                    <p className="mt-3 text-sm font-semibold leading-6">{mod.security_check.result_summary}</p>
                                )}
                                {mod.security_check?.checked_at && (
                                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide opacity-80">
                                        {t('security.checked_at', 'Checked at')}: {new Date(mod.security_check.checked_at).toLocaleString()}
                                    </p>
                                )}
                            </div>
                        </aside>
                    </div>
                </div>

                {auth.user?.id === mod.user?.id && mod.status === 'approved' && (
                    <div className="mt-6 flex justify-end">
                        <Link href={route('mods.versions.create', mod.slug)} className="rounded-xl bg-cyan-400 px-5 py-3 font-bold text-slate-950 hover:bg-cyan-300">
                            {t('mods.submit_version', 'Submit new version')}
                        </Link>
                    </div>
                )}

                {mod.versions && mod.versions.length > 0 && (
                    <section className="mt-8 rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h2 className="text-xl font-bold">{t('mods.version_history', 'Version history')}</h2>
                        <div className="mt-5 space-y-4">
                            {mod.versions.map((version) => (
                                <article key={version.id} className="rounded-xl border border-white/10 bg-slate-950 p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h3 className="text-lg font-black text-white">
                                                {version.version} {version.is_current && <span className="text-sm font-semibold text-cyan-200">({t('mods.current', 'current')})</span>}
                                            </h3>
                                            <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                {version.approved_at ? new Date(version.approved_at).toLocaleDateString() : version.status}
                                            </p>
                                        </div>
                                        {version.status === 'approved' && (
                                            <a href={route('mods.versions.download', [mod.slug, version.id])} target="_blank" rel="noreferrer" className="rounded-lg border border-cyan-300/40 px-4 py-2 text-sm font-bold text-cyan-200 hover:bg-cyan-300/10">
                                                {t('mods.download_version', 'Download version')}
                                            </a>
                                        )}
                                    </div>
                                    <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-200">{version.changelog}</p>
                                    <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                        {t('mods.download_clicks_count', '{count} download clicks').replace('{count}', String(version.download_clicks_count ?? 0))}
                                    </p>
                                </article>
                            ))}
                        </div>
                    </section>
                )}

                <section className="mt-8 grid gap-8 lg:grid-cols-[320px_1fr]">
                    <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h2 className="text-xl font-bold">{t('mods.rate_this_mod', 'Rate this mod')}</h2>
                        {auth.user ? (
                            <form onSubmit={submitRating} className="mt-4 space-y-3">
                                <select
                                    value={ratingForm.data.score}
                                    onChange={(event) => ratingForm.setData('score', event.target.value)}
                                    className="w-full rounded-xl border-white/10 bg-slate-900 text-white focus:border-cyan-400 focus:ring-cyan-400"
                                >
                                    <option value="">{t('mods.select_rating', 'Select rating')}</option>
                                    {[1, 2, 3, 4, 5].map((score) => (
                                        <option key={score} value={score}>{score} / 5</option>
                                    ))}
                                </select>
                                {ratingForm.errors.score && <p className="text-sm text-red-300">{ratingForm.errors.score}</p>}
                                <button disabled={ratingForm.processing} className="w-full rounded-xl bg-cyan-400 px-4 py-3 font-bold text-slate-950 hover:bg-cyan-300 disabled:opacity-50">
                                    {t('mods.save_rating', 'Save rating')}
                                </button>
                            </form>
                        ) : (
                            <p className="mt-4 text-sm text-slate-300">{t('mods.login_to_rate', 'Log in to rate this mod.')}</p>
                        )}
                    </div>

                    <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
                        <h2 className="text-xl font-bold">{t('mods.comments', 'Comments')}</h2>
                        {flash.status && <div className="mt-4 rounded-xl bg-green-400/10 p-3 text-sm text-green-200">{flash.status}</div>}

                        {auth.user ? (
                            <form onSubmit={submitComment} className="mt-5 space-y-3">
                                <textarea
                                    value={commentForm.data.body}
                                    onChange={(event) => commentForm.setData('body', event.target.value)}
                                    rows={4}
                                    placeholder={t('mods.share_feedback', 'Share feedback about this mod')}
                                    className="w-full rounded-xl border-white/10 bg-slate-900 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                />
                                {commentForm.errors.body && <p className="text-sm text-red-300">{commentForm.errors.body}</p>}
                                <button disabled={commentForm.processing} className="rounded-xl bg-cyan-400 px-4 py-3 font-bold text-slate-950 hover:bg-cyan-300 disabled:opacity-50">
                                    {t('mods.post_comment', 'Post comment')}
                                </button>
                            </form>
                        ) : (
                            <p className="mt-4 text-sm text-slate-300">{t('mods.login_to_comment', 'Log in to comment.')}</p>
                        )}

                        <div className="mt-6 space-y-4">
                            {comments.map((comment) => (
                                <article key={comment.id} className="rounded-xl border border-white/10 bg-slate-950 p-4">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <p className="text-sm font-bold text-white">{comment.user.name}</p>
                                        <span className="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase text-slate-300">{comment.status}</span>
                                    </div>
                                    <p className="mt-3 whitespace-pre-line text-sm leading-6 text-slate-200">{comment.body}</p>
                                    {canModerateComments && (
                                        <div className="mt-4 flex gap-2">
                                            {comment.status === 'hidden' ? (
                                                <button onClick={() => router.patch(route('comments.show', comment.id), {}, { preserveScroll: true })} className="rounded-md border border-green-300/40 px-3 py-2 text-sm font-semibold text-green-200 hover:bg-green-300/10">
                                                    {t('actions.show', 'Show')}
                                                </button>
                                            ) : (
                                                <button onClick={() => router.patch(route('comments.hide', comment.id), {}, { preserveScroll: true })} className="rounded-md border border-amber-300/40 px-3 py-2 text-sm font-semibold text-amber-200 hover:bg-amber-300/10">
                                                    {t('actions.hide', 'Hide')}
                                                </button>
                                            )}
                                            <button onClick={() => router.delete(route('comments.destroy', comment.id), { preserveScroll: true })} className="rounded-md border border-red-300/40 px-3 py-2 text-sm font-semibold text-red-200 hover:bg-red-300/10">
                                                {t('actions.delete', 'Delete')}
                                            </button>
                                        </div>
                                    )}
                                </article>
                            ))}
                            {comments.length === 0 && <p className="text-sm text-slate-300">{t('mods.no_comments', 'No comments yet.')}</p>}
                        </div>
                    </div>
                </section>

                {auth.user && (
                    <section className="mt-8">
                        <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
                            <h2 className="text-xl font-bold">{t('mods.report_mod', 'Report this mod')}</h2>
                            <form onSubmit={submitReport} className="mt-4 space-y-3">
                                <select
                                    value={reportForm.data.reason}
                                    onChange={(event) => reportForm.setData('reason', event.target.value)}
                                    className="w-full rounded-xl border-white/10 bg-slate-900 text-white focus:border-cyan-400 focus:ring-cyan-400"
                                >
                                    <option value="">{t('mods.select_reason', 'Select reason')}</option>
                                    <option value="broken_link">{t('mods.reason_broken_link', 'Broken download link')}</option>
                                    <option value="malware">{t('mods.reason_malware', 'Malware or suspicious content')}</option>
                                    <option value="spam">{t('mods.reason_spam', 'Spam or misleading')}</option>
                                    <option value="other">{t('mods.reason_other', 'Other')}</option>
                                </select>
                                <textarea
                                    value={reportForm.data.message}
                                    onChange={(event) => reportForm.setData('message', event.target.value)}
                                    rows={3}
                                    placeholder={t('mods.additional_details', 'Additional details (optional)')}
                                    className="w-full rounded-xl border-white/10 bg-slate-900 text-white placeholder:text-slate-500 focus:border-cyan-400 focus:ring-cyan-400"
                                />
                                {reportForm.errors.reason && <p className="text-sm text-red-300">{reportForm.errors.reason}</p>}
                                {reportForm.errors.message && <p className="text-sm text-red-300">{reportForm.errors.message}</p>}
                                <button disabled={reportForm.processing} className="rounded-xl border border-red-300/40 px-4 py-3 font-bold text-red-200 hover:bg-red-300/10 disabled:opacity-50">
                                    {t('mods.submit_report', 'Submit report')}
                                </button>
                            </form>
                        </div>
                    </section>
                )}
            </main>
        </PublicLayout>
    );
}
