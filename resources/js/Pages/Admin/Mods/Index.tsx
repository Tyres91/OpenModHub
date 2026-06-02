import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslations } from '@/lib/translations';

interface AdminModEntry {
    id: number;
    title: string;
    slug: string;
    description: string;
    external_download_url: string | null;
    virus_total_url: string | null;
    download_clicks_count: number;
    status: 'pending' | 'approved' | 'rejected';
    rejection_reason: string | null;
    approved_at: string | null;
    created_at: string;
    updated_at: string;
    category: { id: number; name: string; slug: string } | null;
    user: { id: number; name: string } | null;
    current_version: { id: number; version: string; external_download_url: string | null } | null;
}

function statusBadge(status: string, t: (key: string, fallback: string) => string) {
    const styles: Record<string, string> = {
        pending: 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
        approved: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        rejected: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    };
    return (
        <span className={`rounded-full px-2 py-0.5 text-xs font-semibold ${styles[status] ?? ''}`}>
            {t(`mods.status_${status}`, status)}
        </span>
    );
}

function ModRow({ mod, categories, canEdit, canDelete, canReview }: { mod: AdminModEntry; categories: Category[]; canEdit: boolean; canDelete: boolean; canReview: boolean }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [editing, setEditing] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deleteConfirmTitle, setDeleteConfirmTitle] = useState('');

    const { data, setData, patch, processing, errors, reset } = useForm({
        title: mod.title,
        description: mod.description,
        category_id: mod.category?.id ?? '',
        external_download_url: mod.external_download_url ?? '',
        virus_total_url: mod.virus_total_url ?? '',
        status: mod.status,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.mods.update', mod.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const deleteMod = () => {
        if (deleteConfirmTitle !== mod.title) {
            return;
        }
        router.delete(route('admin.mods.destroy', mod.id), {
            data: { confirm_title: deleteConfirmTitle },
            preserveScroll: true,
            onSuccess: () => setShowDeleteModal(false),
        });
    };

    if (editing) {
        return (
            <form onSubmit={submit} className="rounded-xl border border-indigo-200 bg-white p-5 shadow-sm dark:border-indigo-800 dark:bg-gray-800">
                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.title', 'Title')}</label>
                        <input value={data.title} onChange={(e) => setData('title', e.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.title && <p className="mt-1 text-xs text-red-600">{errors.title}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.category', 'Category')}</label>
                        <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">{t('mods.select_category', 'Select category')}</option>
                            {categories.map((cat) => (
                                <option key={cat.id} value={cat.id}>{cat.name}</option>
                            ))}
                        </select>
                        {errors.category_id && <p className="mt-1 text-xs text-red-600">{errors.category_id}</p>}
                    </div>
                </div>
                <div className="mt-4">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.description', 'Description')}</label>
                    <textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                    {errors.description && <p className="mt-1 text-xs text-red-600">{errors.description}</p>}
                </div>
                <div className="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.external_download_url', 'Download URL')}</label>
                        <input value={data.external_download_url} onChange={(e) => setData('external_download_url', e.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.external_download_url && <p className="mt-1 text-xs text-red-600">{errors.external_download_url}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.virustotal_url', 'VirusTotal URL')}</label>
                        <input value={data.virus_total_url} onChange={(e) => setData('virus_total_url', e.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.virus_total_url && <p className="mt-1 text-xs text-red-600">{errors.virus_total_url}</p>}
                    </div>
                </div>
                {canReview && (
                    <div className="mt-4">
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.status', 'Status')}</label>
                        <select value={data.status} onChange={(e) => setData('status', e.target.value as 'pending' | 'approved' | 'rejected')} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="pending">{t('mods.status_pending', 'Pending')}</option>
                            <option value="approved">{t('mods.status_approved', 'Approved')}</option>
                            <option value="rejected">{t('mods.status_rejected', 'Rejected')}</option>
                        </select>
                        {errors.status && <p className="mt-1 text-xs text-red-600">{errors.status}</p>}
                    </div>
                )}
                <div className="mt-4 flex gap-2">
                    <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('actions.save', 'Save')}</button>
                    <button type="button" onClick={() => { setEditing(false); reset(); }} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{t('actions.cancel', 'Cancel')}</button>
                </div>
            </form>
        );
    }

    return (
        <>
            <div className="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h3 className="truncate text-lg font-bold text-gray-950 dark:text-white">{mod.title}</h3>
                            {statusBadge(mod.status, t)}
                            {mod.category && (
                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">{mod.category.name}</span>
                            )}
                        </div>
                        <p className="mt-1 line-clamp-2 text-sm text-gray-600 dark:text-gray-300">{mod.description}</p>
                        <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                            {mod.user && <span>{t('admin.mods.author', 'Author')}: {mod.user.name}</span>}
                            {mod.current_version && <span>{t('admin.mods.version', 'Version')}: {mod.current_version.version}</span>}
                            <span>{mod.download_clicks_count} {t('mods.download_clicks_count', 'downloads').replace('{count}', String(mod.download_clicks_count))}</span>
                            <span>{t('admin.mods.created', 'Created')}: {new Date(mod.created_at).toLocaleDateString()}</span>
                        </div>
                        {mod.rejection_reason && (
                            <p className="mt-2 text-xs text-red-600 dark:text-red-400">{t('mods.rejection_reason', 'Rejection reason')}: {mod.rejection_reason}</p>
                        )}
                    </div>
                    <div className="flex shrink-0 flex-wrap gap-2">
                        <Link href={route('mods.show', mod.slug)} className="rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">
                            {t('admin.mods.view_on_site', 'View')}
                        </Link>
                        {canEdit && (
                            <button onClick={() => setEditing(true)} className="rounded-md border border-indigo-300 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:text-indigo-200 dark:hover:bg-indigo-950/40">
                                {t('actions.edit', 'Edit')}
                            </button>
                        )}
                        {canDelete && (
                            <button onClick={() => setShowDeleteModal(true)} className="rounded-md border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-950/40">
                                {t('actions.delete', 'Delete')}
                            </button>
                        )}
                    </div>
                </div>
            </div>

            {showDeleteModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="text-lg font-bold text-red-600 dark:text-red-400">{t('actions.delete', 'Delete')} &quot;{mod.title}&quot;?</h3>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{t('admin.moderation.confirm_permanent', 'This action cannot be undone.')}</p>
                        <div className="mt-4">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.mods.confirm_delete', 'Type the mod title to confirm')}</label>
                            <input type="text" value={deleteConfirmTitle} onChange={(e) => setDeleteConfirmTitle(e.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <div className="mt-4 flex justify-end gap-2">
                            <button onClick={() => { setShowDeleteModal(false); setDeleteConfirmTitle(''); }} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{t('actions.cancel', 'Cancel')}</button>
                            <button onClick={deleteMod} disabled={deleteConfirmTitle !== mod.title} className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50">{t('actions.delete', 'Delete')}</button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

function Pagination({ links }: { links: { url: string | null; label: string; active: boolean }[] }) {
    const validLinks = links.filter((l) => l.url !== null || (!l.url && l.label.includes('&laquo;') || l.label.includes('&raquo;')));
    if (validLinks.length <= 3) {
        return null;
    }
    return (
        <nav className="flex flex-wrap justify-center gap-2 sm:justify-start" aria-label="Pagination">
            {links.map((link, index) =>
                link.url ? (
                    <Link
                        key={`${link.label}-${index}`}
                        href={link.url}
                        preserveScroll
                        className={
                            'rounded-md border px-3 py-2 text-sm transition ' +
                            (link.active
                                ? 'border-indigo-500 bg-indigo-600 text-white'
                                : 'border-gray-200 bg-white text-gray-700 hover:border-indigo-300 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200')
                        }
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <span
                        key={`${link.label}-${index}`}
                        className="rounded-md border border-gray-200 bg-gray-100 px-3 py-2 text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-800/50"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ),
            )}
        </nav>
    );
}

export default function Index({ mods, categories, filters, flash }: PageProps<{ mods: { data: AdminModEntry[]; links: { url: string | null; label: string; active: boolean }[]; from: number | null; to: number | null; total: number }; categories: Category[]; filters: { search: string; status: string; category_id: string; sort_by: string; sort_dir: string }; flash: { status?: string | null; error?: string | null } }>) {
    const { translations, auth } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const user = auth.user;
    const canEdit = user?.permissions?.includes('edit_any_mod') || user?.roles?.includes('admin') || false;
    const canDelete = user?.permissions?.includes('delete_any_mod') || user?.roles?.includes('admin') || false;
    const canReview = user?.permissions?.includes('review_mods') || user?.roles?.includes('admin') || false;

    const [search, setSearch] = useState(filters.search);
    const [status, setStatus] = useState(filters.status);
    const [categoryId, setCategoryId] = useState(filters.category_id);
    const [sortBy, setSortBy] = useState(filters.sort_by);
    const [sortDir, setSortDir] = useState(filters.sort_dir);

    const applyFilters = () => {
        router.get(route('admin.mods.index'), {
            search,
            status: status || undefined,
            category_id: categoryId || undefined,
            sort_by: sortBy,
            sort_dir: sortDir,
        }, { preserveState: true, preserveScroll: true });
    };

    const resetFilters = () => {
        setSearch('');
        setStatus('');
        setCategoryId('');
        setSortBy('created_at');
        setSortDir('desc');
        router.get(route('admin.mods.index'), {}, { preserveState: true, preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.mods.title', 'All Mods')}</h2>}>
            <Head title={t('admin.mods.title', 'All Mods')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.mods.heading', 'Manage mods')}</h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.mods.subtitle', 'Edit mod details, change status, and delete entries.')}</p>
                    </div>

                    {flash?.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}
                    {flash?.error && <div className="mb-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-800">{flash.error}</div>}

                    <div className="mb-6 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800">
                        <div className="flex flex-wrap gap-3">
                            <div className="flex-1 min-w-[200px]">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && applyFilters()}
                                    placeholder={t('admin.mods.search_placeholder', 'Search by title or description')}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <select value={status} onChange={(e) => setStatus(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">{t('admin.mods.all_statuses', 'All statuses')}</option>
                                <option value="pending">{t('mods.status_pending', 'Pending')}</option>
                                <option value="approved">{t('mods.status_approved', 'Approved')}</option>
                                <option value="rejected">{t('mods.status_rejected', 'Rejected')}</option>
                            </select>
                            <select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">{t('admin.mods.all_categories', 'All categories')}</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>{cat.name}</option>
                                ))}
                            </select>
                            <select value={sortBy} onChange={(e) => setSortBy(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="created_at">{t('admin.mods.sort_created_at', 'Created')}</option>
                                <option value="title">{t('admin.mods.sort_title', 'Title')}</option>
                                <option value="download_clicks_count">{t('admin.mods.sort_downloads', 'Downloads')}</option>
                                {canReview && <option value="approved_at">{t('admin.mods.sort_approved_at', 'Approved')}</option>}
                            </select>
                            <select value={sortDir} onChange={(e) => setSortDir(e.target.value)} className="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="desc">{t('admin.mods.sort_desc', 'Descending')}</option>
                                <option value="asc">{t('admin.mods.sort_asc', 'Ascending')}</option>
                            </select>
                            <button onClick={applyFilters} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">{t('actions.filter', 'Filter')}</button>
                            <button onClick={resetFilters} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">{t('actions.back', 'Back')}</button>
                        </div>
                    </div>

                    <div className="space-y-4">
                        {mods.data.map((mod) => (
                            <ModRow key={mod.id} mod={mod} categories={categories} canEdit={canEdit} canDelete={canDelete} canReview={canReview} />
                        ))}
                    </div>

                    {mods.data.length === 0 && (
                        <div className="mt-6 rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {t('admin.mods.no_mods', 'No mods found.')}
                        </div>
                    )}

                    {mods.data.length > 0 && (
                        <div className="mt-6 flex flex-col items-center gap-3">
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {mods.from}–{mods.to} {t('common.mods_count_label', 'of')} {mods.total}
                            </p>
                            <Pagination links={mods.links} />
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
