import AppFooter from '@/Components/AppFooter';
import BrandLogo from '@/Components/BrandLogo';
import CookieConsentBanner from '@/Components/CookieConsentBanner';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ModerationTodoBell from '@/Components/ModerationTodoBell';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';
import { useTranslations } from '@/lib/translations';

export default function PublicLayout({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, moderationTodos, translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const canReview = auth.user?.permissions?.includes('review_mods') || auth.user?.permissions?.includes('moderate_comments') || auth.user?.permissions?.includes('handle_reports');
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    return (
        <div className="flex min-h-screen flex-col bg-slate-950 text-white">
            <header className="border-b border-white/10 bg-slate-950/90">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 sm:py-5">
                    <Link href={route('home')} className="text-xl font-black tracking-tight">
                        <BrandLogo imageClassName="h-8 max-w-[150px] object-contain sm:h-10 sm:max-w-[180px]" fallbackIconClassName="h-8 w-auto fill-current text-cyan-200 sm:h-9" textClassName="text-lg font-black tracking-tight text-white sm:text-xl" />
                    </Link>

                    <nav className="hidden items-center gap-4 text-sm font-semibold sm:flex">
                        <Link href={route('faqs.index')} className="text-slate-200 hover:text-white">
                            {t('navigation.faqs', 'FAQs')}
                        </Link>
                        <LanguageSwitcher />
                        {auth.user ? (
                            <>
                                <Link href={route('dashboard')} className="text-slate-200 hover:text-white">
                                    {t('navigation.dashboard', 'Dashboard')}
                                </Link>
                                <Link href={route('profile.edit')} className="text-slate-200 hover:text-white" title={t('common.account', 'Account')}>
                                    <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0v.75H4.5v-.75z" />
                                    </svg>
                                </Link>
                                <Link href={route('mods.create')} className="rounded-full bg-cyan-400 px-4 py-2 text-slate-950 hover:bg-cyan-300">
                                    {t('mods.submit_mod', 'Submit Mod')}
                                </Link>
                                {canReview && moderationTodos && (
                                    <ModerationTodoBell todos={moderationTodos} t={t} variant="dark" />
                                )}
                            </>
                        ) : (
                            <>
                                <Link href={route('login')} className="text-slate-200 hover:text-white">
                                    {t('auth.login', 'Log in')}
                                </Link>
                                <Link href={route('register')} className="rounded-full bg-cyan-400 px-4 py-2 text-slate-950 hover:bg-cyan-300">
                                    {t('auth.register', 'Register')}
                                </Link>
                            </>
                        )}
                    </nav>

                    <button
                        onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                        className="inline-flex items-center justify-center rounded-md p-2 text-slate-400 hover:bg-white/10 hover:text-white sm:hidden"
                    >
                        <svg className="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            {mobileMenuOpen ? (
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                            ) : (
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 12h16M4 18h16" />
                            )}
                        </svg>
                    </button>
                </div>

                {mobileMenuOpen && (
                    <div className="border-t border-white/10 px-4 pb-4 pt-2 sm:hidden">
                        <div className="space-y-2">
                            <Link href={route('faqs.index')} className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10" onClick={() => setMobileMenuOpen(false)}>
                                {t('navigation.faqs', 'FAQs')}
                            </Link>
                            <div className="px-3 py-2">
                                <LanguageSwitcher />
                            </div>
                            {auth.user ? (
                                <>
                                    <Link href={route('dashboard')} className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10" onClick={() => setMobileMenuOpen(false)}>
                                        {t('navigation.dashboard', 'Dashboard')}
                                    </Link>
                                    <Link href={route('profile.edit')} className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10" onClick={() => setMobileMenuOpen(false)}>
                                        {t('common.account', 'Account')}
                                    </Link>
                                    <Link href={route('mods.create')} className="block rounded-md bg-cyan-400 px-3 py-2 text-center text-sm font-semibold text-slate-950 hover:bg-cyan-300" onClick={() => setMobileMenuOpen(false)}>
                                        {t('mods.submit_mod', 'Submit Mod')}
                                    </Link>
                                    {canReview && moderationTodos && moderationTodos.total > 0 && (
                                        <Link href={route('admin.moderation.index')} className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10" onClick={() => setMobileMenuOpen(false)}>
                                            {t('navigation.moderation_queue', 'Moderation')} ({moderationTodos.total})
                                        </Link>
                                    )}
                                </>
                            ) : (
                                <>
                                    <Link href={route('login')} className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-200 hover:bg-white/10" onClick={() => setMobileMenuOpen(false)}>
                                        {t('auth.login', 'Log in')}
                                    </Link>
                                    <Link href={route('register')} className="block rounded-md bg-cyan-400 px-3 py-2 text-center text-sm font-semibold text-slate-950 hover:bg-cyan-300" onClick={() => setMobileMenuOpen(false)}>
                                        {t('auth.register', 'Register')}
                                    </Link>
                                </>
                            )}
                        </div>
                    </div>
                )}
            </header>

            {header && (
                <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 sm:py-6">
                    {header}
                </div>
            )}

            <main className="flex-1">{children}</main>
            <AppFooter variant="dark" />
            <CookieConsentBanner />
        </div>
    );
}
