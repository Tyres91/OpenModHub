import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Category, PageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent, useState, useRef, ChangeEvent } from 'react';
import { useTranslations } from '@/lib/translations';

const MAX_FILE_SIZE = 5 * 1024 * 1024;
const ACCEPTED_TYPES = ['image/jpeg', 'image/png'];

export default function Create({ categories }: PageProps<{ categories: Pick<Category, 'id' | 'name'>[] }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [preview, setPreview] = useState<string | null>(null);
    const [fileError, setFileError] = useState<string | null>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);

    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        version: '1.0.0',
        changelog: '',
        category_id: '',
        external_download_url: '',
        virus_total_url: '',
        youtube_preview_url: '',
        image: null as File | null,
    });

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;
        setFileError(null);

        if (!file) {
            setPreview(null);
            setData('image', null);
            return;
        }

        if (!ACCEPTED_TYPES.includes(file.type)) {
            setFileError(t('mods.upload_type_error', 'Only JPEG and PNG images are allowed.'));
            setPreview(null);
            setData('image', null);
            if (fileInputRef.current) fileInputRef.current.value = '';
            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            setFileError(t('mods.upload_size_error', 'The image must not exceed 5 MB.'));
            setPreview(null);
            setData('image', null);
            if (fileInputRef.current) fileInputRef.current.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = (ev) => {
            setPreview(ev.target?.result as string);
        };
        reader.readAsDataURL(file);
        setData('image', file);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('mods.store'), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('mods.submit_mod', 'Submit Mod')}</h2>}>
            <Head title={t('mods.submit_mod', 'Submit Mod')} />

            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.title', 'Title')}</label>
                            <input value={data.title} onChange={(event) => setData('title', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            {errors.title && <p className="mt-2 text-sm text-red-600">{errors.title}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.category', 'Category')}</label>
                            <select value={data.category_id} onChange={(event) => setData('category_id', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">
                                <option value="">{t('mods.select_category', 'Select category')}</option>
                                {categories.map((category) => (
                                    <option key={category.id} value={category.id}>{category.name}</option>
                                ))}
                            </select>
                            {errors.category_id && <p className="mt-2 text-sm text-red-600">{errors.category_id}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.description', 'Description')}</label>
                            <textarea value={data.description} onChange={(event) => setData('description', event.target.value)} rows={8} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            {errors.description && <p className="mt-2 text-sm text-red-600">{errors.description}</p>}
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.version', 'Version')}</label>
                                <input value={data.version} onChange={(event) => setData('version', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('mods.version_hint', 'Use semantic versions such as 1.0.0, 1.2.0-beta1, or v2.0.0-RC1.')}</p>
                                {errors.version && <p className="mt-2 text-sm text-red-600">{errors.version}</p>}
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.changelog', 'Changelog')}</label>
                                <textarea value={data.changelog} onChange={(event) => setData('changelog', event.target.value)} rows={4} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                                {errors.changelog && <p className="mt-2 text-sm text-red-600">{errors.changelog}</p>}
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.external_download_url', 'External download URL')}</label>
                            <input value={data.external_download_url} onChange={(event) => setData('external_download_url', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            {errors.external_download_url && <p className="mt-2 text-sm text-red-600">{errors.external_download_url}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.virustotal_url', 'VirusTotal URL')}</label>
                            <input value={data.virus_total_url} onChange={(event) => setData('virus_total_url', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            {errors.virus_total_url && <p className="mt-2 text-sm text-red-600">{errors.virus_total_url}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.youtube_preview_url', 'YouTube preview URL')}</label>
                            <input value={data.youtube_preview_url} onChange={(event) => setData('youtube_preview_url', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('mods.youtube_preview_hint', 'Optional. The video is shown as a click-to-load preview for privacy.')}</p>
                            {errors.youtube_preview_url && <p className="mt-2 text-sm text-red-600">{errors.youtube_preview_url}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.screenshot', 'Screenshot')}</label>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/png,image/jpeg"
                                onChange={handleFileChange}
                                className="mt-1 w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500 dark:text-gray-200 dark:file:bg-indigo-700 dark:hover:file:bg-indigo-600"
                            />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('mods.screenshot_hint', 'PNG or JPEG, minimum 512x512px, maximum 5 MB.')}</p>
                            {fileError && <p className="mt-2 text-sm text-red-600">{fileError}</p>}
                            {errors.image && <p className="mt-2 text-sm text-red-600">{errors.image}</p>}
                            {preview && (
                                <div className="mt-3">
                                    <img src={preview} alt="Preview" className="max-h-48 rounded-md border border-gray-200 dark:border-gray-700" />
                                </div>
                            )}
                        </div>

                        <button disabled={processing} className="rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                            {t('mods.submit_for_review', 'Submit for review')}
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
