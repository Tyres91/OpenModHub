import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/react';

type LocalePageProps = PageProps & {
    locale: string;
    defaultLocale: string;
    availableLocales: Record<string, string>;
};

export default function LanguageSwitcher() {
    const page = usePage<LocalePageProps>();
    const { locale, defaultLocale, availableLocales, auth } = page.props;
    const currentLabel = availableLocales[locale] ?? locale;
    const isSystemDefault = !auth.user?.locale && locale === defaultLocale;

    const switchLocale = (targetLocale: string) => {
        router.post(route('locale.update'), { locale: targetLocale }, {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ data: { locale: targetLocale } });
            },
            onError: () => {
                window.location.reload();
            },
        });
    };

    return (
        <div className="relative inline-block">
            <select
                value={locale}
                onChange={(e) => switchLocale(e.target.value)}
                className="rounded-md border border-gray-300 bg-white px-2 py-1 text-sm text-gray-700 focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 disabled:cursor-not-allowed disabled:opacity-50"
                title={isSystemDefault ? `System default (${currentLabel})` : currentLabel}
            >
                {Object.entries(availableLocales).map(([code, label]) => (
                    <option key={code} value={code}>
                        {code === defaultLocale && isSystemDefault ? `${label} (default)` : label}
                    </option>
                ))}
            </select>
        </div>
    );
}
