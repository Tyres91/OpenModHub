import { Link, usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useTranslations } from '@/lib/translations';

export default function AppFooter({ variant = 'light' }: { variant?: 'light' | 'dark' }) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const dark = variant === 'dark';

    const openCookieSettings = () => {
        window.dispatchEvent(new Event('open-cookie-settings'));
    };

    return (
        <footer className={dark ? 'border-t border-white/10 bg-slate-950 text-slate-300' : 'border-t border-gray-200 bg-white text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300'}>
            <div className="mx-auto flex max-w-7xl flex-col gap-3 px-6 py-6 text-sm sm:flex-row sm:items-center sm:justify-between">
                <p>&copy; {new Date().getFullYear()} OpenModHub</p>
                <nav className="flex flex-wrap gap-4">
                    <Link href={route('faqs.index')} className="hover:underline">
                        {t('navigation.faqs', 'FAQs')}
                    </Link>
                    <Link href={route('legal.imprint')} className="hover:underline">
                        {t('legal.imprint', 'Imprint')}
                    </Link>
                    <Link href={route('legal.privacy')} className="hover:underline">
                        {t('legal.privacy', 'Privacy Policy')}
                    </Link>
                    <button type="button" onClick={openCookieSettings} className="hover:underline">
                        {t('legal.cookie_settings', 'Cookie settings')}
                    </button>
                </nav>
            </div>
        </footer>
    );
}
