import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import RichTextEditor from '@/Components/RichTextEditor';
import { PageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslations } from '@/lib/translations';

type EmailTemplate = {
    id: number;
    key: string;
    subject_en: string;
    subject_de: string;
    body_en: string;
    body_de: string;
    is_active: boolean;
    placeholders: Record<string, string>;
    updated_at: string;
};

type Props = PageProps<{
    templates: EmailTemplate[];
}>;

const KEY_LABELS: Record<string, string> = {
    verify_email: 'Email Verification',
    mod_approved: 'Mod Approved',
    mod_rejected: 'Mod Rejected',
    version_approved: 'Version Approved',
    version_rejected: 'Version Rejected',
};

export default function Index({ templates, flash }: Props) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [editingId, setEditingId] = useState<number | null>(null);

    const startEdit = (template: EmailTemplate) => {
        setEditingId(template.id);
        form.setData({
            subject_en: template.subject_en,
            subject_de: template.subject_de,
            body_en: template.body_en,
            body_de: template.body_de,
            is_active: template.is_active,
        });
    };

    const cancelEdit = () => {
        setEditingId(null);
        form.clearErrors();
    };

    const submit = (template: EmailTemplate) => {
        form.patch(route('admin.email-templates.update', template.id), {
            onSuccess: () => setEditingId(null),
        });
    };

    const form = useForm({
        subject_en: '',
        subject_de: '',
        body_en: '',
        body_de: '',
        is_active: true,
    });

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.email_templates.title', 'Email Templates')}</h2>}>
            <Head title={t('admin.email_templates.title', 'Email Templates')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.email_templates.heading', 'Manage email templates')}</h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.email_templates.subtitle', 'Configure notification emails sent to users. Use placeholders that are replaced dynamically.')}</p>
                    </div>

                    {flash.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}

                    <div className="space-y-6">
                        {templates.map((template) => (
                            <div key={template.id} className="rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                                <div className="mb-4 flex items-center justify-between">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-950 dark:text-white">{KEY_LABELS[template.key] ?? template.key}</h3>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">Key: <code className="rounded bg-gray-100 px-1 dark:bg-gray-700">{template.key}</code></p>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <span className={`rounded-full px-2 py-1 text-xs font-semibold ${template.is_active ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'}`}>
                                            {template.is_active ? 'Active' : 'Inactive'}
                                        </span>
                                        {editingId !== template.id && (
                                            <button onClick={() => startEdit(template)} className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                                {t('actions.edit', 'Edit')}
                                            </button>
                                        )}
                                    </div>
                                </div>

                                {editingId === template.id ? (
                                    <div className="space-y-4">
                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('admin.email_templates.subject_en', 'Subject (English)')}</label>
                                                <input
                                                    value={form.data.subject_en}
                                                    onChange={(e) => form.setData('subject_en', e.target.value)}
                                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                                />
                                                {form.errors.subject_en && <p className="mt-1 text-sm text-red-600">{form.errors.subject_en}</p>}
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('admin.email_templates.subject_de', 'Subject (German)')}</label>
                                                <input
                                                    value={form.data.subject_de}
                                                    onChange={(e) => form.setData('subject_de', e.target.value)}
                                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                                />
                                                {form.errors.subject_de && <p className="mt-1 text-sm text-red-600">{form.errors.subject_de}</p>}
                                            </div>
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('admin.email_templates.body_en', 'Body (English)')}</label>
                                            <div className="mt-1">
                                                <RichTextEditor
                                                    value={form.data.body_en}
                                                    onChange={(value) => form.setData('body_en', value)}
                                                    placeholder="Email body content..."
                                                    placeholders={template.placeholders}
                                                />
                                            </div>
                                            {form.errors.body_en && <p className="mt-1 text-sm text-red-600">{form.errors.body_en}</p>}
                                        </div>

                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{t('admin.email_templates.body_de', 'Body (German)')}</label>
                                            <div className="mt-1">
                                                <RichTextEditor
                                                    value={form.data.body_de}
                                                    onChange={(value) => form.setData('body_de', value)}
                                                    placeholder="E-Mail-Inhalt..."
                                                    placeholders={template.placeholders}
                                                />
                                            </div>
                                            {form.errors.body_de && <p className="mt-1 text-sm text-red-600">{form.errors.body_de}</p>}
                                        </div>

                                        <label className="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                            <input
                                                type="checkbox"
                                                checked={form.data.is_active}
                                                onChange={(e) => form.setData('is_active', e.target.checked)}
                                                className="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900"
                                            />
                                            <span className="block font-semibold">{t('admin.email_templates.is_active', 'Active')}</span>
                                        </label>

                                        <div className="flex gap-3">
                                            <button type="button" onClick={() => submit(template)} disabled={form.processing} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                                                {t('actions.save', 'Save')}
                                            </button>
                                            <button type="button" onClick={cancelEdit} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                                {t('actions.cancel', 'Cancel')}
                                            </button>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                        <p><strong className="text-gray-900 dark:text-gray-200">EN:</strong> {template.subject_en}</p>
                                        <p><strong className="text-gray-900 dark:text-gray-200">DE:</strong> {template.subject_de}</p>
                                        <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                            <p className="mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400">Preview (English):</p>
                                            <div className="prose prose-sm max-w-none dark:prose-invert" dangerouslySetInnerHTML={{ __html: template.body_en }} />
                                        </div>
                                    </div>
                                )}
                            </div>
                        ))}

                        {templates.length === 0 && (
                            <div className="rounded-2xl bg-white p-12 text-center shadow-sm dark:bg-gray-800">
                                <p className="text-gray-500 dark:text-gray-400">{t('admin.email_templates.no_templates', 'No email templates found.')}</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
