import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, Permission, Rank, Role } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslations } from '@/lib/translations';

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
    block_reason: string | null;
    created_at: string;
}

function UserRow({ user, roles, permissions, specialRanks, availableLocales }: { user: AdminUserEntry; roles: Role[]; permissions: Permission[]; specialRanks: Pick<Rank, 'id' | 'name' | 'color' | 'icon'>[]; availableLocales: Record<string, string> }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [editing, setEditing] = useState(false);

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

    const blockUser = () => {
        const reason = window.prompt(t('admin.users.block_reason_prompt', 'Reason for blocking this user (optional)'));

        if (reason === null) {
            return;
        }

        router.patch(route('admin.users.block', user.id), { block_reason: reason }, { preserveScroll: true });
    };

    const unblockUser = () => {
        router.patch(route('admin.users.unblock', user.id), {}, { preserveScroll: true });
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
                    </div>
                    <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        {user.mods_count} {t('common.mods_count_label', 'mods')} &middot; {t('admin.users.joined', 'Joined')} {new Date(user.created_at).toLocaleDateString()}
                    </p>
                    {user.blocked_at && (
                        <p className="mt-2 text-sm text-red-700 dark:text-red-300">
                            {t('admin.users.blocked_since', 'Blocked since')} {new Date(user.blocked_at).toLocaleDateString()}
                            {user.block_reason ? ` · ${user.block_reason}` : ''}
                        </p>
                    )}
                </div>
                <div className="flex gap-2">
                    <button onClick={() => setEditing(true)} className="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">{t('actions.edit', 'Edit')}</button>
                    {user.blocked_at ? (
                        <button onClick={unblockUser} className="rounded-md border border-green-300 px-3 py-2 text-sm font-semibold text-green-700 hover:bg-green-50 dark:border-green-800 dark:text-green-200 dark:hover:bg-green-950/40">{t('actions.unblock', 'Unblock')}</button>
                    ) : (
                        <button onClick={blockUser} className="rounded-md border border-red-300 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-950/40">{t('actions.block', 'Block')}</button>
                    )}
                </div>
            </div>
        </article>
    );
}

export default function Index({ users, roles, permissions, specialRanks, availableLocales, flash }: PageProps<{ users: AdminUserEntry[]; roles: Role[]; permissions: Permission[]; specialRanks: Pick<Rank, 'id' | 'name' | 'color' | 'icon'>[]; availableLocales: Record<string, string> }>) {
    const { translations } = usePage<PageProps>().props;
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

                    <div className="space-y-4">
                        {users.map((user) => (
                            <UserRow key={user.id} user={user} roles={roles} permissions={permissions} specialRanks={specialRanks} availableLocales={availableLocales} />
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
