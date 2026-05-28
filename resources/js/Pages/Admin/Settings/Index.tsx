import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEvent } from 'react';
import { useTranslations } from '@/lib/translations';

type LegalSettings = {
    legal_operator_name: string;
    legal_represented_by: string;
    legal_street: string;
    legal_postal_code: string;
    legal_city: string;
    legal_country: string;
    legal_email: string;
    legal_phone: string;
    legal_vat_id: string;
    legal_privacy_contact: string;
    legal_additional_info: string;
};

type SettingsProps = PageProps<{
    defaultLocale: string;
    availableLocales: Record<string, string>;
    googleTagManagerId: string;
    debugMode: boolean;
    modSubmissionsBlocked: boolean;
    modPendingSubmissionLimit: number;
    siteLogoUrl: string | null;
    siteLogoText: string;
    siteLogoShowText: boolean;
    faviconMode: 'auto' | 'manual';
    hasFavicons: boolean;
    warningExpiryDays: number;
    sanctionUploadBanThreshold: number;
    sanctionUploadBanDays: number;
    sanctionAccountLockThreshold: number;
    sanctionAccountLockDays: number;
    legalSettings: LegalSettings;
}>;

export default function Index({ defaultLocale, availableLocales, googleTagManagerId, debugMode, modSubmissionsBlocked, modPendingSubmissionLimit, siteLogoUrl, siteLogoText, siteLogoShowText, faviconMode, hasFavicons, warningExpiryDays, sanctionUploadBanThreshold, sanctionUploadBanDays, sanctionAccountLockThreshold, sanctionAccountLockDays, legalSettings, flash }: SettingsProps) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const form = useForm({
        default_locale: defaultLocale,
        google_tag_manager_id: googleTagManagerId ?? '',
        debug_mode: debugMode,
        mod_submissions_blocked: modSubmissionsBlocked,
        mod_pending_submission_limit: modPendingSubmissionLimit,
        site_logo_text: siteLogoText,
        site_logo_show_text: siteLogoShowText,
        warning_expiry_days: warningExpiryDays,
        sanction_upload_ban_threshold: sanctionUploadBanThreshold,
        sanction_upload_ban_days: sanctionUploadBanDays,
        sanction_account_lock_threshold: sanctionAccountLockThreshold,
        sanction_account_lock_days: sanctionAccountLockDays,
        ...legalSettings,
    });
    const logoForm = useForm<{ logo: File | null }>({ logo: null });
    const faviconForm = useForm<{ favicon: File | null }>({ favicon: null });

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.patch(route('admin.settings.update'));
    };

    const submitLogo = () => {
        logoForm.post(route('admin.settings.logo.update'), {
            forceFormData: true,
            onSuccess: () => logoForm.reset('logo'),
        });
    };

    const removeLogo = () => {
        logoForm.delete(route('admin.settings.logo.destroy'), { preserveScroll: true });
    };

    const submitFavicon = () => {
        faviconForm.post(route('admin.settings.favicon.update'), {
            forceFormData: true,
            onSuccess: () => faviconForm.reset('favicon'),
        });
    };

    const resetFavicon = () => {
        faviconForm.delete(route('admin.settings.favicon.destroy'), { preserveScroll: true });
    };

    const regenerateFavicons = () => {
        faviconForm.post(route('admin.settings.favicon.regenerate'), { preserveScroll: true });
    };

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">{t('admin.settings.title', 'Settings')}</h2>}>
            <Head title={t('admin.settings.title', 'Settings')} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold text-gray-950 dark:text-white">{t('admin.settings.heading', 'Application settings')}</h1>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.subtitle', 'Configure global application settings.')}</p>
                    </div>

                    {flash.status && <div className="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800">{flash.status}</div>}

                    <form onSubmit={submit} className="space-y-6 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <section className="max-w-2xl space-y-4">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.branding_heading', 'Branding')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.branding_hint', 'Upload a local logo and configure the displayed logo text.')}</p>
                            </div>

                            {siteLogoUrl && (
                                <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300">{t('admin.settings.current_logo', 'Current logo')}</p>
                                    <img src={siteLogoUrl} alt={form.data.site_logo_text || 'OpenModHub'} className="mt-3 max-h-24 max-w-xs object-contain" />
                                </div>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {t('admin.settings.logo_text', 'Logo text')}
                                </label>
                                <input
                                    value={form.data.site_logo_text}
                                    onChange={(e) => form.setData('site_logo_text', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                {form.errors.site_logo_text && <p className="mt-1 text-sm text-red-600">{form.errors.site_logo_text}</p>}
                            </div>

                            <label className="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={form.data.site_logo_show_text}
                                    onChange={(event) => form.setData('site_logo_show_text', event.target.checked)}
                                    className="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900"
                                />
                                <span>
                                    <span className="block font-semibold">{t('admin.settings.show_logo_text', 'Show logo text')}</span>
                                    <span className="mt-1 block text-gray-600 dark:text-gray-400">{t('admin.settings.show_logo_text_hint', 'Disable this if the uploaded logo already contains text.')}</span>
                                </span>
                            </label>
                            {form.errors.site_logo_show_text && <p className="mt-1 text-sm text-red-600">{form.errors.site_logo_show_text}</p>}
                        </section>

                        <section className="max-w-2xl space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.logo_upload_heading', 'Logo upload')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.logo_upload_hint', 'PNG, JPG, or WebP. Maximum size: 2 MB.')}</p>
                            </div>
                            <div className="flex flex-wrap items-end gap-3">
                                <div className="min-w-0 flex-1">
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp"
                                        onChange={(event) => logoForm.setData('logo', event.target.files?.[0] ?? null)}
                                        className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500 dark:text-gray-300"
                                    />
                                    {logoForm.errors.logo && <p className="mt-1 text-sm text-red-600">{logoForm.errors.logo}</p>}
                                </div>
                                <button type="button" onClick={submitLogo} disabled={logoForm.processing || !logoForm.data.logo} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                                    {t('admin.settings.upload_logo', 'Upload logo')}
                                </button>
                                {siteLogoUrl && (
                                    <button type="button" onClick={removeLogo} disabled={logoForm.processing} className="rounded-md border border-red-300 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50 dark:border-red-800 dark:text-red-200 dark:hover:bg-red-950/40">
                                        {t('admin.settings.remove_logo', 'Remove logo')}
                                    </button>
                                )}
                            </div>
                        </section>

                        <section className="max-w-2xl space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.favicon_heading', 'Favicon')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                    {faviconMode === 'auto' 
                                        ? t('admin.settings.favicon_auto_hint', 'Favicons werden automatisch aus dem Logo generiert.')
                                        : t('admin.settings.favicon_manual_hint', 'Ein manuelles Favicon wurde hochgeladen.')
                                    }
                                </p>
                            </div>

                            <div className="flex items-center gap-4">
                                <div className="flex items-center justify-center rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-900">
                                    <img src="/favicon.ico" alt="Favicon Preview" className="h-8 w-8 object-contain" onError={(e) => (e.currentTarget.src = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMiIgaGVpZ2h0PSIzMiIgdmlld0JveD0iMCAwIDMyIDMyIj48cmVjdCB3aWR0aD0iMzIiIGhlaWdodD0iMzIiIGZpbGw9IiNlNWU3ZWIiLz48dGV4dCB4PSI1MCUiIHk9IjUwJSIgZG9taW5hbnQtYmFzZWxpbmU9Im1pZGRsZSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzZiNzI4MCI+PC90ZXh0Pjwvc3ZnPg==')} />
                                </div>
                                <div className="text-sm">
                                    <p className="font-medium text-gray-700 dark:text-gray-300">
                                        {hasFavicons ? t('admin.settings.favicon_status_generated', 'Favicon generiert') : t('admin.settings.favicon_status_missing', 'Kein Favicon vorhanden')}
                                    </p>
                                    <p className="text-gray-500 dark:text-gray-400">
                                        {faviconMode === 'auto' ? t('admin.settings.favicon_mode_auto', 'Automatisch aus Logo') : t('admin.settings.favicon_mode_manual', 'Manuell hochgeladen')}
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-end gap-3">
                                <div className="min-w-0 flex-1">
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/x-icon"
                                        onChange={(event) => faviconForm.setData('favicon', event.target.files?.[0] ?? null)}
                                        className="block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-indigo-500 dark:text-gray-300"
                                    />
                                    {faviconForm.errors.favicon && <p className="mt-1 text-sm text-red-600">{faviconForm.errors.favicon}</p>}
                                </div>
                                <button type="button" onClick={submitFavicon} disabled={faviconForm.processing || !faviconForm.data.favicon} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">
                                    {t('admin.settings.upload_favicon', 'Favicon hochladen')}
                                </button>
                                {hasFavicons && faviconMode === 'manual' && (
                                    <button type="button" onClick={resetFavicon} disabled={faviconForm.processing} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                                        {t('admin.settings.reset_favicon', 'Zurücksetzen')}
                                    </button>
                                )}
                                {siteLogoUrl && (
                                    <button type="button" onClick={regenerateFavicons} disabled={faviconForm.processing} className="rounded-md border border-indigo-300 px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 disabled:opacity-50 dark:border-indigo-800 dark:text-indigo-200 dark:hover:bg-indigo-950/40">
                                        {t('admin.settings.regenerate_favicon', 'Aus Logo generieren')}
                                    </button>
                                )}
                            </div>
                        </section>

                        <section className="max-w-2xl space-y-4">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.localization_heading', 'Localization')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.localization_hint', 'Choose the default language for visitors without their own preference.')}</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {t('admin.settings.default_locale', 'Default language')}
                                </label>
                                <select
                                    value={form.data.default_locale}
                                    onChange={(e) => form.setData('default_locale', e.target.value)}
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                >
                                    {Object.entries(availableLocales).map(([code, label]) => (
                                        <option key={code} value={code}>{label}</option>
                                    ))}
                                </select>
                                {form.errors.default_locale && <p className="mt-1 text-sm text-red-600">{form.errors.default_locale}</p>}
                            </div>
                        </section>

                        <section className="max-w-2xl space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.tracking_heading', 'Tracking')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.gtm_hint', 'Google Tag Manager is only loaded after explicit analytics consent.')}</p>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {t('admin.settings.gtm_id', 'Google Tag Manager ID')}
                                </label>
                                <input
                                    value={form.data.google_tag_manager_id}
                                    onChange={(e) => form.setData('google_tag_manager_id', e.target.value.toUpperCase())}
                                    placeholder={t('admin.settings.gtm_placeholder', 'GTM-XXXXXXX')}
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                {form.errors.google_tag_manager_id && <p className="mt-1 text-sm text-red-600">{form.errors.google_tag_manager_id}</p>}
                            </div>
                        </section>

                        <section className="max-w-2xl space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.submissions_heading', 'Mod submissions')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.submissions_hint', 'Control how regular users can submit new mods.')}</p>
                            </div>
                            <label className="flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={form.data.mod_submissions_blocked}
                                    onChange={(event) => form.setData('mod_submissions_blocked', event.target.checked)}
                                    className="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900"
                                />
                                <span>
                                    <span className="block font-semibold">{t('admin.settings.block_mod_submissions', 'Block regular user mod submissions')}</span>
                                    <span className="mt-1 block text-gray-600 dark:text-gray-400">{t('admin.settings.block_mod_submissions_hint', 'Admins and users with review permissions can still submit mods.')}</span>
                                </span>
                            </label>
                            {form.errors.mod_submissions_blocked && <p className="mt-1 text-sm text-red-600">{form.errors.mod_submissions_blocked}</p>}

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {t('admin.settings.pending_mod_limit', 'Pending mod submission limit')}
                                </label>
                                <input
                                    type="number"
                                    min={0}
                                    max={100}
                                    value={form.data.mod_pending_submission_limit}
                                    onChange={(e) => form.setData('mod_pending_submission_limit', Number(e.target.value))}
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.pending_mod_limit_hint', '0 means unlimited. Approved and rejected mods do not count.')}</p>
                                {form.errors.mod_pending_submission_limit && <p className="mt-1 text-sm text-red-600">{form.errors.mod_pending_submission_limit}</p>}
                            </div>
                        </section>

                        <section className="max-w-2xl space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.moderation_heading', 'Moderation and sanctions')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.moderation_hint', 'Configure warning expiry and automatic sanction thresholds.')}</p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {t('admin.settings.warning_expiry_days', 'Warning expiry (days)')}
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={3650}
                                        value={form.data.warning_expiry_days}
                                        onChange={(e) => form.setData('warning_expiry_days', Number(e.target.value))}
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('admin.settings.warning_expiry_days_hint', 'Active warnings expire after this many days.')}</p>
                                    {form.errors.warning_expiry_days && <p className="mt-1 text-sm text-red-600">{form.errors.warning_expiry_days}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {t('admin.settings.sanction_upload_ban_threshold', 'Upload ban threshold (points)')}
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={1000}
                                        value={form.data.sanction_upload_ban_threshold}
                                        onChange={(e) => form.setData('sanction_upload_ban_threshold', Number(e.target.value))}
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('admin.settings.sanction_upload_ban_threshold_hint', 'Active warning points at which an upload ban is applied.')}</p>
                                    {form.errors.sanction_upload_ban_threshold && <p className="mt-1 text-sm text-red-600">{form.errors.sanction_upload_ban_threshold}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {t('admin.settings.sanction_upload_ban_days', 'Upload ban duration (days)')}
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={3650}
                                        value={form.data.sanction_upload_ban_days}
                                        onChange={(e) => form.setData('sanction_upload_ban_days', Number(e.target.value))}
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('admin.settings.sanction_upload_ban_days_hint', 'How long an automatic upload ban lasts.')}</p>
                                    {form.errors.sanction_upload_ban_days && <p className="mt-1 text-sm text-red-600">{form.errors.sanction_upload_ban_days}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {t('admin.settings.sanction_account_lock_threshold', 'Account lock threshold (points)')}
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={1000}
                                        value={form.data.sanction_account_lock_threshold}
                                        onChange={(e) => form.setData('sanction_account_lock_threshold', Number(e.target.value))}
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('admin.settings.sanction_account_lock_threshold_hint', 'Active warning points at which an account lock is applied.')}</p>
                                    {form.errors.sanction_account_lock_threshold && <p className="mt-1 text-sm text-red-600">{form.errors.sanction_account_lock_threshold}</p>}
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {t('admin.settings.sanction_account_lock_days', 'Account lock duration (days)')}
                                    </label>
                                    <input
                                        type="number"
                                        min={1}
                                        max={3650}
                                        value={form.data.sanction_account_lock_days}
                                        onChange={(e) => form.setData('sanction_account_lock_days', Number(e.target.value))}
                                        className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                    />
                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{t('admin.settings.sanction_account_lock_days_hint', 'How long an automatic account lock lasts.')}</p>
                                    {form.errors.sanction_account_lock_days && <p className="mt-1 text-sm text-red-600">{form.errors.sanction_account_lock_days}</p>}
                                </div>
                            </div>
                        </section>

                        <section className="max-w-2xl space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.development_heading', 'Development')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.debug_mode_hint', 'Show local development helpers such as registration verification links.')}</p>
                            </div>
                            <label className="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                                <input
                                    type="checkbox"
                                    checked={form.data.debug_mode}
                                    onChange={(event) => form.setData('debug_mode', event.target.checked)}
                                    className="mt-1 rounded border-amber-300 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:border-amber-700 dark:bg-gray-900"
                                />
                                <span>
                                    <span className="block font-semibold">{t('admin.settings.debug_mode', 'Debug mode')}</span>
                                    <span className="mt-1 block text-amber-800 dark:text-amber-200">{t('admin.settings.debug_mode_description', 'When enabled, newly registered users see their signed email verification URL on the verification prompt. Keep this disabled outside local development.')}</span>
                                </span>
                            </label>
                            {form.errors.debug_mode && <p className="mt-1 text-sm text-red-600">{form.errors.debug_mode}</p>}
                        </section>

                        <section className="space-y-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                            <div>
                                <h2 className="text-lg font-semibold text-gray-950 dark:text-white">{t('admin.settings.legal_heading', 'Legal information')}</h2>
                                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('admin.settings.legal_hint', 'These details are used for the imprint and privacy pages.')}</p>
                            </div>

                            <div className="grid gap-4 md:grid-cols-2">
                                <TextField label={t('admin.settings.legal_operator_name', 'Operator / company')} value={form.data.legal_operator_name} error={form.errors.legal_operator_name} onChange={(value) => form.setData('legal_operator_name', value)} />
                                <TextField label={t('admin.settings.legal_represented_by', 'Represented by')} value={form.data.legal_represented_by} error={form.errors.legal_represented_by} onChange={(value) => form.setData('legal_represented_by', value)} />
                                <TextField label={t('admin.settings.legal_street', 'Street and house number')} value={form.data.legal_street} error={form.errors.legal_street} onChange={(value) => form.setData('legal_street', value)} />
                                <TextField label={t('admin.settings.legal_postal_code', 'Postal code')} value={form.data.legal_postal_code} error={form.errors.legal_postal_code} onChange={(value) => form.setData('legal_postal_code', value)} />
                                <TextField label={t('admin.settings.legal_city', 'City')} value={form.data.legal_city} error={form.errors.legal_city} onChange={(value) => form.setData('legal_city', value)} />
                                <TextField label={t('admin.settings.legal_country', 'Country')} value={form.data.legal_country} error={form.errors.legal_country} onChange={(value) => form.setData('legal_country', value)} />
                                <TextField label={t('admin.settings.legal_email', 'Email')} value={form.data.legal_email} error={form.errors.legal_email} onChange={(value) => form.setData('legal_email', value)} />
                                <TextField label={t('admin.settings.legal_phone', 'Phone')} value={form.data.legal_phone} error={form.errors.legal_phone} onChange={(value) => form.setData('legal_phone', value)} />
                                <TextField label={t('admin.settings.legal_vat_id', 'VAT ID')} value={form.data.legal_vat_id} error={form.errors.legal_vat_id} onChange={(value) => form.setData('legal_vat_id', value)} />
                                <TextField label={t('admin.settings.legal_privacy_contact', 'Privacy contact')} value={form.data.legal_privacy_contact} error={form.errors.legal_privacy_contact} onChange={(value) => form.setData('legal_privacy_contact', value)} />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {t('admin.settings.legal_additional_info', 'Additional legal information')}
                                </label>
                                <textarea
                                    value={form.data.legal_additional_info}
                                    onChange={(e) => form.setData('legal_additional_info', e.target.value)}
                                    rows={5}
                                    className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                                />
                                {form.errors.legal_additional_info && <p className="mt-1 text-sm text-red-600">{form.errors.legal_additional_info}</p>}
                            </div>
                        </section>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50"
                        >
                            {t('actions.save', 'Save')}
                        </button>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}

function TextField({ label, value, error, onChange }: { label: string; value: string; error?: string; onChange: (value: string) => void }) {
    return (
        <div>
            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">{label}</label>
            <input
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            />
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}
