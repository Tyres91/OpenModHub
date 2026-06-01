import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { ModEntry, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ChangeEvent, FormEvent, useRef, useState } from 'react';
import { useTranslations } from '@/lib/translations';

const MAX_AUDIO_SIZE = 20 * 1024 * 1024;

export default function CreateVersion({ mod }: PageProps<{ mod: Pick<ModEntry, 'id' | 'title' | 'slug'> }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [audioError, setAudioError] = useState<string | null>(null);
    const audioInputRef = useRef<HTMLInputElement>(null);
    const { data, setData, post, processing, errors } = useForm({
        version: '',
        changelog: '',
        external_download_url: '',
        virus_total_url: '',
        youtube_preview_url: '',
        audio_file: null as File | null,
    });

    const handleAudioChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0] ?? null;
        setAudioError(null);

        if (!file) {
            setData('audio_file', null);
            return;
        }

        if (file.type !== 'audio/mpeg' && !file.name.toLowerCase().endsWith('.mp3')) {
            setAudioError(t('mods.audio_type_error', 'Only MP3 audio files are allowed.'));
            setData('audio_file', null);
            if (audioInputRef.current) audioInputRef.current.value = '';
            return;
        }

        if (file.size > MAX_AUDIO_SIZE) {
            setAudioError(t('mods.audio_size_error', 'The MP3 file must not exceed 20 MB.'));
            setData('audio_file', null);
            if (audioInputRef.current) audioInputRef.current.value = '';
            return;
        }

        setData('audio_file', file);
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        post(route('mods.versions.store', mod.slug), { forceFormData: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('mods.submit_version', 'Submit new version')}</h2>}>
            <Head title={t('mods.submit_version', 'Submit new version')} />

            <div className="py-12">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <Link href={route('mods.show', mod.slug)} className="text-sm font-semibold text-indigo-600 dark:text-indigo-300">
                            {t('actions.back', 'Back')}
                        </Link>
                        <h1 className="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{mod.title}</h1>
                    </div>

                    <form onSubmit={submit} className="space-y-6 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.version', 'Version')}</label>
                            <input value={data.version} onChange={(event) => setData('version', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('mods.version_hint', 'Use semantic versions such as 1.0.0, 1.2.0-beta1, or v2.0.0-RC1.')}</p>
                            {errors.version && <p className="mt-2 text-sm text-red-600">{errors.version}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.changelog', 'Changelog')}</label>
                            <textarea value={data.changelog} onChange={(event) => setData('changelog', event.target.value)} rows={7} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            {errors.changelog && <p className="mt-2 text-sm text-red-600">{errors.changelog}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.external_download_url', 'External download URL')}</label>
                            <input value={data.external_download_url} onChange={(event) => setData('external_download_url', event.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('mods.external_download_or_audio_hint', 'Required unless you upload an MP3 file as the download.')}</p>
                            {errors.external_download_url && <p className="mt-2 text-sm text-red-600">{errors.external_download_url}</p>}
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-200">{t('mods.audio_file', 'MP3 audio file')}</label>
                            <input ref={audioInputRef} type="file" accept="audio/mpeg,.mp3" onChange={handleAudioChange} className="mt-1 w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500 dark:text-gray-200 dark:file:bg-indigo-700 dark:hover:file:bg-indigo-600" />
                            <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('mods.audio_file_hint', 'Optional MP3 preview or soundmod download, maximum 20 MB.')}</p>
                            {audioError && <p className="mt-2 text-sm text-red-600">{audioError}</p>}
                            {errors.audio_file && <p className="mt-2 text-sm text-red-600">{errors.audio_file}</p>}
                            {data.audio_file && <p className="mt-2 text-sm text-gray-600 dark:text-gray-300">{data.audio_file.name}</p>}
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

                        <button disabled={processing} className="rounded-md bg-indigo-600 px-5 py-3 font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                            {t('mods.submit_for_review', 'Submit for review')}
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
