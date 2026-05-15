import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RankIcon from '@/Components/RankIcon';
import RankIconSelector from '@/Components/RankIconSelector';
import RankColorInput from '@/Components/RankColorInput';
import { PageProps, Rank } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import { useTranslations } from '@/lib/translations';

function RankRow({ rank }: { rank: Rank }) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const [editing, setEditing] = useState(false);
    const { data, setData, patch, processing, errors } = useForm({
        name: rank.name,
        required_points: String(rank.required_points),
        color: rank.color,
        icon: rank.icon ?? '',
        is_special: rank.is_special,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.ranks.update', rank.id), {
            preserveScroll: true,
            onSuccess: () => setEditing(false),
        });
    };

    const destroy = () => {
        if (window.confirm(t('common.delete_rank_confirm', `Delete rank "${rank.name}"?`).replace('{name}', rank.name))) {
            router.delete(route('admin.ranks.destroy', rank.id), { preserveScroll: true });
        }
    };

    if (editing) {
        return (
            <form onSubmit={submit} className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
                <div className="grid gap-4 md:grid-cols-2">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.name', 'Name')}</label>
                        <input value={data.name} onChange={(event) => setData('name', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.required_points', 'Required points')}</label>
                        <input type="number" min="0" value={data.required_points} onChange={(event) => setData('required_points', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                        {errors.required_points && <p className="mt-1 text-sm text-red-600">{errors.required_points}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.color', 'Color')}</label>
                        <RankColorInput value={data.color} onChange={(value) => setData('color', value)} className="mt-1" />
                        {errors.color && <p className="mt-1 text-sm text-red-600">{errors.color}</p>}
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.icon', 'Icon (optional)')}</label>
                        <RankIconSelector value={data.icon} onChange={(value) => setData('icon', value)} className="mt-1" />
                        {errors.icon && <p className="mt-1 text-sm text-red-600">{errors.icon}</p>}
                    </div>
                    <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                        <input type="checkbox" checked={data.is_special} onChange={(event) => setData('is_special', event.target.checked)} />
                        {t('admin.ranks.special_rank', 'Special rank')}
                    </label>
                </div>
                <div className="mt-4 flex gap-2">
                    <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('actions.save', 'Save')}</button>
                    <button type="button" onClick={() => setEditing(false)} className="rounded-md px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">{t('actions.cancel', 'Cancel')}</button>
                </div>
            </form>
        );
    }

    return (
        <article className="rounded-2xl bg-white p-5 shadow-sm dark:bg-gray-800">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <div className="flex items-center gap-3">
                    <span className="inline-flex size-8 items-center justify-center rounded-full" style={{ backgroundColor: rank.color }}>
                        {rank.icon && <RankIcon value={rank.icon} className="text-white" />}
                    </span>
                    <div>
                        <h2 className="text-lg font-bold text-gray-950 dark:text-white">{rank.name}</h2>
                        <p className="text-sm text-gray-600 dark:text-gray-300">
                            {rank.is_special
                                ? t('admin.ranks.special_rank_locked', 'Special rank, assigned manually.')
                                : t('common.requires_points', 'Requires {count} points.').replace('{count}', String(rank.required_points))}
                        </p>
                    </div>
                </div>
                <div className="flex gap-2">
                    <button onClick={() => setEditing(true)} className="rounded-md border border-gray-300 px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700">{t('actions.edit', 'Edit')}</button>
                    <button onClick={destroy} className="rounded-md border border-red-200 px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950">{t('actions.delete', 'Delete')}</button>
                </div>
            </div>
        </article>
    );
}

export default function Index({ ranks, flash }: PageProps<{ ranks: Rank[] }>) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        required_points: '10',
        color: '#4f46e5',
        icon: '',
        is_special: false,
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('admin.ranks.store'), {
            preserveScroll: true,
            onSuccess: () => reset('name', 'icon'),
        });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.ranks.title', 'Ranks')}</h2>}>
            <Head title={t('admin.ranks.title', 'Ranks')} />

            <div className="py-12">
                <div className="mx-auto grid max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[360px_1fr] lg:px-8">
                    <form onSubmit={submit} className="h-fit rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h1 className="text-xl font-bold text-gray-950 dark:text-white">{t('admin.ranks.new_rank', 'New rank')}</h1>
                        {flash.status && <div className="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{flash.status}</div>}

                        <div className="mt-5 space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.name', 'Name')}</label>
                                <input value={data.name} onChange={(event) => setData('name', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.required_points', 'Required points')}</label>
                                <input type="number" min="0" value={data.required_points} onChange={(event) => setData('required_points', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.required_points && <p className="mt-1 text-sm text-red-600">{errors.required_points}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.color', 'Color')}</label>
                                <RankColorInput value={data.color} onChange={(value) => setData('color', value)} className="mt-1" />
                                {errors.color && <p className="mt-1 text-sm text-red-600">{errors.color}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.ranks.icon', 'Icon (optional)')}</label>
                                <RankIconSelector value={data.icon} onChange={(value) => setData('icon', value)} className="mt-1" />
                                {errors.icon && <p className="mt-1 text-sm text-red-600">{errors.icon}</p>}
                            </div>
                            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                                <input type="checkbox" checked={data.is_special} onChange={(event) => setData('is_special', event.target.checked)} />
                                {t('admin.ranks.special_rank', 'Special rank')}
                            </label>
                            <button disabled={processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">{t('common.create', 'Create')}</button>
                        </div>
                    </form>

                    <section className="space-y-4">
                        {ranks.map((rank) => <RankRow key={rank.id} rank={rank} />)}
                    </section>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
