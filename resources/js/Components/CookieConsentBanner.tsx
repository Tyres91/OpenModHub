import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { useTranslations } from '@/lib/translations';

const CONSENT_KEY = 'openmodhub_cookie_consent';

type ConsentState = {
    necessary: true;
    analytics: boolean;
};

function loadConsent(): ConsentState | null {
    try {
        const value = window.localStorage.getItem(CONSENT_KEY);
        return value ? JSON.parse(value) as ConsentState : null;
    } catch {
        return null;
    }
}

function saveConsent(consent: ConsentState) {
    window.localStorage.setItem(CONSENT_KEY, JSON.stringify(consent));
}

function loadGoogleTagManager(id: string) {
    if (!id || document.querySelector(`script[data-gtm-id="${id}"]`)) {
        return;
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });

    const script = document.createElement('script');
    script.async = true;
    script.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(id)}`;
    script.dataset.gtmId = id;
    document.head.appendChild(script);
}

declare global {
    interface Window {
        dataLayer?: Array<Record<string, unknown>>;
    }
}

export default function CookieConsentBanner() {
    const { googleTagManagerId, translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const [isOpen, setIsOpen] = useState(false);
    const [analytics, setAnalytics] = useState(false);

    useEffect(() => {
        const consent = loadConsent();

        if (!consent) {
            setIsOpen(true);
            return;
        }

        setAnalytics(consent.analytics);

        if (consent.analytics) {
            loadGoogleTagManager(googleTagManagerId);
        }
    }, [googleTagManagerId]);

    useEffect(() => {
        const open = () => setIsOpen(true);
        window.addEventListener('open-cookie-settings', open);
        return () => window.removeEventListener('open-cookie-settings', open);
    }, []);

    const persist = (allowAnalytics: boolean) => {
        const consent: ConsentState = { necessary: true, analytics: allowAnalytics };
        saveConsent(consent);
        setAnalytics(allowAnalytics);
        setIsOpen(false);

        if (allowAnalytics) {
            loadGoogleTagManager(googleTagManagerId);
        }
    };

    if (!isOpen) {
        return null;
    }

    return (
        <div className="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white p-4 shadow-2xl dark:border-gray-700 dark:bg-gray-900">
            <div className="mx-auto max-w-5xl space-y-4">
                <div>
                    <h2 className="text-lg font-bold text-gray-950 dark:text-white">{t('consent.title', 'Privacy settings')}</h2>
                    <p className="mt-1 text-sm leading-6 text-gray-600 dark:text-gray-300">
                        {t('consent.description', 'We only use necessary storage by default. Analytics via Google Tag Manager is loaded only after your consent.')}
                    </p>
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                    <label className="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" checked disabled className="mr-2" />
                        <span className="font-semibold text-gray-900 dark:text-white">{t('consent.necessary', 'Necessary')}</span>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('consent.necessary_description', 'Required for security, language selection, sessions, and cookie choices.')}</p>
                    </label>
                    <label className="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" checked={analytics} onChange={(e) => setAnalytics(e.target.checked)} className="mr-2" />
                        <span className="font-semibold text-gray-900 dark:text-white">{t('consent.analytics', 'Analytics')}</span>
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{t('consent.analytics_description', 'Allows Google Tag Manager to help us understand visitor numbers and usage.')}</p>
                    </label>
                </div>

                <div className="flex flex-wrap gap-3">
                    <button type="button" onClick={() => persist(true)} className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        {t('consent.accept_all', 'Accept all')}
                    </button>
                    <button type="button" onClick={() => persist(false)} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                        {t('consent.reject_all', 'Reject')}
                    </button>
                    <button type="button" onClick={() => persist(analytics)} className="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                        {t('consent.save_selection', 'Save selection')}
                    </button>
                </div>
            </div>
        </div>
    );
}
