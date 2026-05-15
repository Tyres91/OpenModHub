import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps, RankPointRule } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { useTranslations } from '@/lib/translations';

export default function Index({ pointRules, flash }: PageProps<{ pointRules: RankPointRule[] }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const { data, setData, patch, processing, errors } = useForm({
        rules: pointRules.map((rule) => ({
            key: rule.key,
            points: String(rule.points),
            threshold: rule.threshold === null || rule.threshold === undefined ? '' : String(rule.threshold),
            is_enabled: rule.is_enabled,
        })),
    });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        patch(route('admin.rank-point-rules.update'), { preserveScroll: true });
    };

    const updateRule = (index: number, field: 'points' | 'threshold' | 'is_enabled', value: string | boolean) => {
        setData('rules', data.rules.map((rule, ruleIndex) => ruleIndex === index ? { ...rule, [field]: value } : rule));
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.rank_point_rules.title', 'Point Rules')}</h2>}>
            <Head title={t('admin.rank_point_rules.title', 'Point Rules')} />

            <div className="py-12">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.rank_point_rules.heading', 'Manage point rules')}</h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.rank_point_rules.subtitle', 'Changing these values recalculates all user ranks retroactively.')}</p>
                        </div>

                        {flash.status && <div className="mt-4 rounded-md bg-green-50 p-3 text-sm text-green-800">{flash.status}</div>}

                        <div className="mt-6 space-y-4">
                            {pointRules.map((rule, index) => (
                                <div key={rule.key} className="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                            <h2 className="font-semibold text-gray-950 dark:text-white">{t(`admin.rank_point_rules.rule_${rule.key}`, rule.label)}</h2>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">{rule.key}</p>
                                        </div>
                                        <label className="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                            <input type="checkbox" checked={data.rules[index]?.is_enabled ?? false} onChange={(event) => updateRule(index, 'is_enabled', event.target.checked)} />
                                            {t('common.active', 'Active')}
                                        </label>
                                    </div>
                                    <div className="mt-3 grid gap-3 md:grid-cols-2">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('admin.rank_point_rules.points', 'Points')}</label>
                                            <input type="number" min="0" value={data.rules[index]?.points ?? ''} onChange={(event) => updateRule(index, 'points', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                        </div>
                                        {(rule.key === 'download_threshold' || rule.key === 'rating_average_bonus') && (
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">
                                                    {rule.key === 'rating_average_bonus'
                                                        ? t('admin.rank_point_rules.min_ratings_threshold', 'Minimum ratings')
                                                        : t('admin.rank_point_rules.download_threshold', 'Download threshold')}
                                                </label>
                                                <input type="number" min="1" value={data.rules[index]?.threshold ?? ''} onChange={(event) => updateRule(index, 'threshold', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                        {errors.rules && <p className="mt-2 text-sm text-red-600">{errors.rules}</p>}
                        <button disabled={processing} className="mt-6 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                            {t('actions.save', 'Save')}
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
