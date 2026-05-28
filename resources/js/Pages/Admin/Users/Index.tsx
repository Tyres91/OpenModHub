import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Permission, Rank, Role } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslations } from '@/lib/translations';

interface WarningEntry {
    id: number;
    points: number;
    reason: string;
    status: 'active' | 'expired' | 'removed';
    issued_by: string | null;
    issued_at: string;
    expires_at: string | null;
    removed_by: string | null;
    removed_at: string | null;
}

interface SanctionEntry {
    id: number;
    type: 'upload_ban' | 'account_lock';
    reason: string;
    active: boolean;
    issued_by: string | null;
    issued_at: string;
    expires_at: string | null;
    removed_by: string | null;
    removed_at: string | null;
}

interface AdminUserEntry {
    id: number;
    name: string;
    email: string;
    locale: string | null;
    roles: string[];
    permissions: string[];
    email_verified_at: string | null;
    mods_count: number;
    rank_id: number | null;
    special_rank: Pick<Rank, 'id' | 'name' | 'color' | 'icon'> | null;
    blocked_at: string | null;
    blocked_until: string | null;
    blocked_by: number | null;
    block_reason: string | null;
    created_at: string;
    active_warning_points: number;
    warnings: WarningEntry[];
    sanctions: SanctionEntry[];
}

function WarningSection({ user }: { user: AdminUserEntry }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        points: '1',
        reason: '',
        expires_at: '',
    });

    const submitWarning = (e: FormEvent) => {
        e.preventDefault();
        post(route('admin.users.warnings.store', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                setShowForm(false);
                reset();
            },
        });
    };

    const removeWarning = (warningId: number) => {
        if (!confirm(t('warnings.confirm_remove', 'Are you sure you want to remove this warning?'))) {
            return;
        }
        router.delete(route('admin.warnings.destroy', warningId), { preserveScroll: true });
    };

    return (
        <div className="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {t('warnings.title', 'Warnings')} ({t('warnings.active_points', 'Active points')}: {user.active_warning_points})
                </h3>
                <button
                    onClick={() => setShowForm(!showForm)}
                    className="rounded-md bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-500"
                >
                    {t('warnings.add_warning', 'Add warning')}
                </button>
            </div>

            {showForm && (
                <form onSubmit={submitWarning} className="mt-3 space-y-3 rounded-md border border-gray-300 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">{t('warnings.points', 'Points')}</label>
                        <input
                            type="number"
                            min="1"
                            max="100"
                            value={data.points}
                            onChange={(e) => setData('points', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                        {errors.points && <p className="mt-1 text-xs text-red-600">{errors.points}</p>}
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">{t('warnings.reason', 'Reason')}</label>
                        <textarea
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            rows={2}
                        />
                        {errors.reason && <p className="mt-1 text-xs text-red-600">{errors.reason}</p>}
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">{t('warnings.expires_at', 'Expires at')} (optional)</label>
                        <input
                            type="datetime-local"
                            value={data.expires_at}
                            onChange={(e) => setData('expires_at', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                        {errors.expires_at && <p className="mt-1 text-xs text-red-600">{errors.expires_at}</p>}
                    </div>
                    <div className="flex gap-2">
                        <button disabled={processing} className="rounded-md bg-amber-600 px-3 py-1 text-xs font-semibold text-white hover:bg-amber-500 disabled:opacity-50">
                            {t('actions.save', 'Save')}
                        </button>
                        <button type="button" onClick={() => setShowForm(false)} className="rounded-md px-3 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                            {t('actions.cancel', 'Cancel')}
                        </button>
                    </div>
                </form>
            )}

            {user.warnings.length === 0 ? (
                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('warnings.no_warnings', 'No warnings.')}</p>
            ) : (
                <div className="mt-3 space-y-2">
                    {user.warnings.map((warning) => (
                        <div key={warning.id} className={`rounded-md border p-2 text-xs ${
                            warning.status === 'active'
                                ? 'border-amber-300 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/30'
                                : warning.status === 'removed'
                                    ? 'border-gray-300 bg-gray-100 dark:border-gray-700 dark:bg-gray-800'
                                    : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800'
                        }`}>
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <span className="font-semibold">{warning.points} {t('warnings.points', 'points')}</span>
                                    <span className={`ml-2 rounded px-1 py-0.5 text-xs ${
                                        warning.status === 'active' ? 'bg-amber-200 text-amber-800 dark:bg-amber-800 dark:text-amber-200' :
                                        warning.status === 'removed' ? 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-400' :
                                        'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                                    }`}>
                                        {t(`warnings.status_${warning.status}`, warning.status)}
                                    </span>
                                    <p className="mt-1 text-gray-600 dark:text-gray-300">{warning.reason}</p>
                                    <p className="mt-1 text-gray-500 dark:text-gray-400">
                                        {t('warnings.issued_by', 'Issued by')} {warning.issued_by ?? '—'} · {new Date(warning.issued_at).toLocaleDateString()}
                                        {warning.expires_at && ` · ${t('warnings.expires_at', 'Expires')} ${new Date(warning.expires_at).toLocaleDateString()}`}
                                    </p>
                                    {warning.removed_by && (
                                        <p className="text-gray-500 dark:text-gray-400">
                                            {t('warnings.status_removed', 'Removed')} by {warning.removed_by} · {new Date(warning.removed_at!).toLocaleDateString()}
                                        </p>
                                    )}
                                </div>
                                {warning.status === 'active' && (
                                    <button
                                        onClick={() => removeWarning(warning.id)}
                                        className="shrink-0 rounded border border-red-300 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-950/40"
                                    >
                                        {t('warnings.remove_warning', 'Remove')}
                                    </button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

function SanctionSection({ user }: { user: AdminUserEntry }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [showForm, setShowForm] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({
        type: 'upload_ban' as string,
        reason: '',
        expires_at: '',
    });

    const submitSanction = (e: FormEvent) => {
        e.preventDefault();
        post(route('admin.users.sanctions.store', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                setShowForm(false);
                reset();
            },
        });
    };

    const removeSanction = (sanctionId: number) => {
        if (!confirm(t('user_sanctions.confirm_remove', 'Are you sure you want to remove this sanction?'))) {
            return;
        }
        router.delete(route('admin.sanctions.destroy', sanctionId), { preserveScroll: true });
    };

    const activeSanctions = user.sanctions.filter((s) => s.active);

    return (
        <div className="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
            <div className="flex items-center justify-between">
                <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {t('user_sanctions.title', 'Sanctions')} ({activeSanctions.length} active)
                </h3>
                <button
                    onClick={() => setShowForm(!showForm)}
                    className="rounded-md bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-500"
                >
                    {t('user_sanctions.add_sanction', 'Add sanction')}
                </button>
            </div>

            {showForm && (
                <form onSubmit={submitSanction} className="mt-3 space-y-3 rounded-md border border-gray-300 bg-white p-3 dark:border-gray-600 dark:bg-gray-800">
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">{t('user_sanctions.type', 'Type')}</label>
                        <select
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        >
                            <option value="upload_ban">{t('user_sanctions.type_upload_ban', 'Upload ban')}</option>
                            <option value="account_lock">{t('user_sanctions.type_account_lock', 'Account lock')}</option>
                        </select>
                        {errors.type && <p className="mt-1 text-xs text-red-600">{errors.type}</p>}
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">{t('user_sanctions.reason', 'Reason')}</label>
                        <textarea
                            value={data.reason}
                            onChange={(e) => setData('reason', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            rows={2}
                        />
                        {errors.reason && <p className="mt-1 text-xs text-red-600">{errors.reason}</p>}
                    </div>
                    <div>
                        <label className="block text-xs font-medium text-gray-700 dark:text-gray-200">{t('user_sanctions.expires_at', 'Expires at')} (optional)</label>
                        <input
                            type="datetime-local"
                            value={data.expires_at}
                            onChange={(e) => setData('expires_at', e.target.value)}
                            className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                        />
                        {errors.expires_at && <p className="mt-1 text-xs text-red-600">{errors.expires_at}</p>}
                    </div>
                    <div className="flex gap-2">
                        <button disabled={processing} className="rounded-md bg-red-600 px-3 py-1 text-xs font-semibold text-white hover:bg-red-500 disabled:opacity-50">
                            {t('actions.save', 'Save')}
                        </button>
                        <button type="button" onClick={() => setShowForm(false)} className="rounded-md px-3 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                            {t('actions.cancel', 'Cancel')}
                        </button>
                    </div>
                </form>
            )}

            {activeSanctions.length === 0 ? (
                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">{t('user_sanctions.no_sanctions', 'No active sanctions.')}</p>
            ) : (
                <div className="mt-3 space-y-2">
                    {activeSanctions.map((sanction) => (
                        <div key={sanction.id} className="rounded-md border border-red-300 bg-red-50 p-2 text-xs dark:border-red-800 dark:bg-red-950/30">
                            <div className="flex items-start justify-between gap-2">
                                <div>
                                    <span className="font-semibold">
                                        {sanction.type === 'upload_ban'
                                            ? t('user_sanctions.type_upload_ban', 'Upload ban')
                                            : t('user_sanctions.type_account_lock', 'Account lock')}
                                    </span>
                                    <p className="mt-1 text-gray-600 dark:text-gray-300">{sanction.reason}</p>
                                    <p className="mt-1 text-gray-500 dark:text-gray-400">
                                        {t('warnings.issued_by', 'Issued by')} {sanction.issued_by ?? '—'} · {new Date(sanction.issued_at).toLocaleDateString()}
                                        {sanction.expires_at && ` · ${t('user_sanctions.expires_at', 'Expires')} ${new Date(sanction.expires_at).toLocaleDateString()}`}
                                    </p>
                                </div>
                                <button
                                    onClick={() => removeSanction(sanction.id)}
                                    className="shrink-0 rounded border border-red-300 px-2 py-1 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-950/40"
                                >
                                    {t('user_sanctions.remove_sanction', 'Remove')}
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}

function UserRow({ user, roles, permissions, specialRanks, availableLocales, authUserId, authUserPermissions, authUserRoles }: { user: AdminUserEntry; roles: Role[]; permissions: Permission[]; specialRanks: Pick<Rank, 'id' | 'name' | 'color' | 'icon'>[]; availableLocales: Record<string, string>; authUserId: number | null; authUserPermissions: string[]; authUserRoles: string[] }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [editing, setEditing] = useState(false);
    const [showModeration, setShowModeration] = useState(false);
    const [showBlockModal, setShowBlockModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deleteConfirmName, setDeleteConfirmName] = useState('');

    const canModerate = authUserPermissions.includes('moderate_users') || authUserRoles.includes('admin');
    const canManageUsers = authUserPermissions.includes('manage_users') || authUserRoles.includes('admin');
    const canDelete = authUserRoles.includes('admin') && user.id !== authUserId;

    const { data, setData, patch, processing, errors, reset } = useForm({
        name: user.name,
        email: user.email,
        locale: user.locale ?? '',
        password: '',
        password_confirmation: '',
        rank_id: user.rank_id ? String(user.rank_id) : '',
        roles: [...user.roles],
        permissions: [...user.permissions],
    });

    const blockForm = useForm({
        block_reason: '',
        blocked_until: '',
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.users.update', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(false);
                reset('password', 'password_confirmation');
            },
        });
    };

    const toggleRole = (slug: string) => {
        const current = data.roles;
        if (current.includes(slug)) {
            setData('roles', current.filter((r) => r !== slug));
        } else {
            setData('roles', [...current, slug]);
        }
    };

    const togglePermission = (slug: string) => {
        const current = data.permissions;
        if (current.includes(slug)) {
            setData('permissions', current.filter((p) => p !== slug));
        } else {
            setData('permissions', [...current, slug]);
        }
    };

    const groupedPermissions = permissions.reduce((acc, perm) => {
        if (!acc[perm.group]) {
            acc[perm.group] = [];
        }
        acc[perm.group].push(perm);
        return acc;
    }, {} as Record<string, Permission[]>);

    const submitBlock = (e: FormEvent) => {
        e.preventDefault();
        blockForm.patch(route('admin.users.block', user.id), {
            preserveScroll: true,
            onSuccess: () => {
                setShowBlockModal(false);
                blockForm.reset();
            },
        });
    };

    const unblockUser = () => {
        router.patch(route('admin.users.unblock', user.id), {}, { preserveScroll: true });
    };

    const deleteUser = () => {
        if (deleteConfirmName !== user.name) {
            return;
        }
        router.delete(route('admin.users.destroy', user.id), {
            preserveScroll: true,
            onSuccess: () => setShowDeleteModal(false),
        });
    };

    if (editing) {
        return (
            <form onSubmit={submit} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <div className="grid gap-4 lg:grid-cols-[1fr_1fr_auto]">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('auth.name', 'Name')}</label>
                        <input value={data.name} onChange={(event) => setData('name', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('auth.email', 'Email')}</label>
                        <input type="email" value={data.email} onChange={(event) => setData('email', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.email && <p className="mt-1 text-sm text-red-600">{errors.email}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('profile.language', 'Language')}</label>
                        <select value={data.locale} onChange={(event) => setData('locale', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">{t('profile.system_default', 'System default')}</option>
                            {Object.entries(availableLocales).map(([code, label]) => (
                                <option key={code} value={code}>{label}</option>
                            ))}
                        </select>
                        {errors.locale && <p className="mt-1 text-sm text-red-600">{errors.locale}</p>}
                    </div>
                </div>

                <div className="mt-4 grid gap-4 lg:grid-cols-[1fr_1fr]">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.users.new_password', 'New password (optional)')}</label>
                        <input type="password" value={data.password} onChange={(event) => setData('password', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.password && <p className="mt-1 text-sm text-red-600">{errors.password}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.users.confirm_password', 'Confirm password')}</label>
                        <input type="password" value={data.password_confirmation} onChange={(event) => setData('password_confirmation', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.password_confirmation && <p className="mt-1 text-sm text-red-600">{errors.password_confirmation}</p>}
                    </div>
                </div>

                <div className="mt-4">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.users.special_rank', 'Special rank')}</label>
                    <select value={data.rank_id} onChange={(event) => setData('rank_id', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                        <option value="">{t('admin.users.no_special_rank', 'No special rank')}</option>
                        {specialRanks.map((rank) => (
                            <option key={rank.id} value={rank.id}>{rank.name}</option>
                        ))}
                    </select>
                    {errors.rank_id && <p className="mt-1 text-sm text-red-600">{errors.rank_id}</p>}
                </div>

                <div className="mt-4">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.users.roles_label', 'Roles')}</label>
                    <div className="mt-2 flex flex-wrap gap-3">
                        {roles.map((role) => (
                            <label key={role.slug} className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input type="checkbox" checked={data.roles.includes(role.slug)} onChange={() => toggleRole(role.slug)} />
                                {role.name}
                            </label>
                        ))}
                    </div>
                    {errors.roles && <p className="mt-1 text-sm text-red-600">{errors.roles}</p>}
                </div>

                <div className="mt-6">
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.users.permissions_label', 'Permissions')}</label>
                    <div className="mt-3 grid gap-4 sm:grid-cols-2">
                        {Object.entries(groupedPermissions).map(([group, groupPermissions]) => (
                            <div key={group} className="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                <h4 className="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                    {t(`permissions.groups.${group}`, group.charAt(0).toUpperCase() + group.slice(1))}
                                </h4>
                                <div className="space-y-2">
                                    {groupPermissions.map((permission) => (
                                        <label key={permission.slug} className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300">
                                            <input
                                                type="checkbox"
                                                checked={data.permissions.includes(permission.slug)}
                                                onChange={() => togglePermission(permission.slug)}
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800"
                                            />
                                            {t(`permissions.permissions.${permission.slug}`, permission.name)}
                                        </label>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                    {errors.permissions && <p className="mt-1 text-sm text-red-600">{errors.permissions}</p>}
                </div>

                <div className="mt-4 flex gap-2">
                    <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('actions.save', 'Save')}</button>
                    <button type="button" onClick={() => { setEditing(false); reset('password', 'password_confirmation'); }} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{t('actions.cancel', 'Cancel')}</button>
                </div>
            </form>
        );
    }

    return (
        <article className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div className="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <div className="flex flex-wrap items-center gap-3">
                        <h2 className="text-lg font-bold text-gray-950 dark:text-white">{user.name}</h2>
                        <span className="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-200">{user.email}</span>
                        {user.roles.map((role) => (
                            <span key={role} className="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold capitalize text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200">{role}</span>
                        ))}
                        {user.special_rank && (
                            <span className="rounded-full px-3 py-1 text-xs font-semibold text-white" style={{ backgroundColor: user.special_rank.color }}>
                                {user.special_rank.name}
                            </span>
                        )}
                        {user.blocked_at && (
                            <span className="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-200">
                                {t('admin.users.blocked', 'Blocked')}
                            </span>
                        )}
                        {user.active_warning_points > 0 && (
                            <span className="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-200">
                                {user.active_warning_points} {t('warnings.points', 'pts')}
                            </span>
                        )}
                    </div>
                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {user.mods_count} {t('common.mods_count_label', 'mods')} &middot; {t('admin.users.joined', 'Joined')} {new Date(user.created_at).toLocaleDateString()}
                    </p>
                    {user.blocked_at && (
                        <p className="mt-2 text-sm text-red-700 dark:text-red-300">
                            {user.blocked_until
                                ? t('admin.users.blocked_temporarily', 'Blocked until').replace(':date', new Date(user.blocked_until).toLocaleString())
                                : t('admin.users.blocked_permanently', 'Permanently blocked')
                            }
                            {user.block_reason ? ` · ${user.block_reason}` : ''}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    {canManageUsers && (
                        <button onClick={() => setEditing(true)} className="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">{t('actions.edit', 'Edit')}</button>
                    )}
                    {canModerate && (
                        <button onClick={() => setShowModeration(!showModeration)} className="rounded-md border border-amber-300 px-3 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-200 dark:hover:bg-amber-950/40">
                            {showModeration ? t('actions.hide', 'Hide') : t('warnings.title', 'Moderation')}
                        </button>
                    )}
                    {canManageUsers && (
                        user.blocked_at ? (
                            <button onClick={unblockUser} className="rounded-md border border-green-300 px-3 py-2 text-sm font-semibold text-green-700 hover:bg-green-50 dark:border-green-800 dark:text-green-200 dark:hover:bg-green-950/40">{t('actions.unblock', 'Unblock')}</button>
                        ) : (
                            <button onClick={() => setShowBlockModal(true)} className="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-950/40">{t('actions.block', 'Block')}</button>
                        )
                    )}
                    {canDelete && (
                        <button onClick={() => setShowDeleteModal(true)} className="rounded-md border border-red-600 bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-500">{t('admin.users.delete_user', 'Delete')}</button>
                    )}
                </div>
            </div>

            {showModeration && canModerate && (
                <div>
                    <WarningSection user={user} />
                    <SanctionSection user={user} />
                </div>
            )}

            {showBlockModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white">{t('actions.block', 'Block')} {user.name}</h3>
                        <form onSubmit={submitBlock} className="mt-4 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {t('admin.users.block_reason_required', 'Reason')} *
                                </label>
                                <textarea
                                    value={blockForm.data.block_reason}
                                    onChange={(e) => blockForm.setData('block_reason', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                    rows={3}
                                    required
                                />
                                {blockForm.errors.block_reason && <p className="mt-1 text-sm text-red-600">{blockForm.errors.block_reason}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                    {t('admin.users.blocked_until', 'Blocked until')} (optional)
                                </label>
                                <input
                                    type="datetime-local"
                                    value={blockForm.data.blocked_until}
                                    onChange={(e) => blockForm.setData('blocked_until', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                />
                                {blockForm.errors.blocked_until && <p className="mt-1 text-sm text-red-600">{blockForm.errors.blocked_until}</p>}
                            </div>
                            <div className="flex justify-end gap-2">
                                <button type="button" onClick={() => { setShowBlockModal(false); blockForm.reset(); }} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                    {t('actions.cancel', 'Cancel')}
                                </button>
                                <button type="submit" disabled={blockForm.processing} className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50">
                                    {t('actions.block', 'Block')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {showDeleteModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-gray-800">
                        <h3 className="text-lg font-bold text-red-600 dark:text-red-400">{t('admin.users.delete_confirm_title', 'Delete user permanently?')}</h3>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                            {t('admin.users.delete_confirm_text', 'This action cannot be undone. All data will be permanently deleted.')}
                        </p>
                        <div className="mt-4">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                {t('admin.users.delete_type_name', 'Type the username to confirm.')}
                            </label>
                            <input
                                type="text"
                                value={deleteConfirmName}
                                onChange={(e) => setDeleteConfirmName(e.target.value)}
                                className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            />
                        </div>
                        <div className="mt-4 flex justify-end gap-2">
                            <button onClick={() => { setShowDeleteModal(false); setDeleteConfirmName(''); }} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                {t('actions.cancel', 'Cancel')}
                            </button>
                            <button
                                onClick={deleteUser}
                                disabled={deleteConfirmName !== user.name}
                                className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-50"
                            >
                                {t('admin.users.delete_user', 'Delete permanently')}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </article>
    );
}

export default function Index({ users, roles, permissions, specialRanks, availableLocales, flash }: PageProps<{ users: AdminUserEntry[]; roles: Role[]; permissions: Permission[]; specialRanks: Pick<Rank, 'id' | 'name' | 'color' | 'icon'>[]; availableLocales: Record<string, string> }>) {
    const { translations, auth } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.users.title', 'Users')}</h2>}>
            <Head title={t('admin.users.title', 'Users')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.users.heading', 'Manage users')}</h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.users.subtitle', 'Edit user details, assign roles, and manage passwords.')}</p>
                    </div>

                    {flash.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}
                    {flash.error && <div className="mb-6 rounded-md bg-red-50 p-4 text-sm font-medium text-red-800">{flash.error}</div>}

                    <div className="space-y-4">
                        {users.map((user) => (
                            <UserRow key={user.id} user={user} roles={roles} permissions={permissions} specialRanks={specialRanks} availableLocales={availableLocales} authUserId={auth.user?.id ?? null} authUserPermissions={auth.user?.permissions ?? []} authUserRoles={auth.user?.roles ?? []} />
                        ))}
                    </div>

                    {users.length === 0 && (
                        <div className="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {t('admin.users.no_users', 'No users found.')}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
