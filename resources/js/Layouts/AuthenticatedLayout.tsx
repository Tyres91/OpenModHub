import AppFooter from '@/Components/AppFooter';
import BrandLogo from '@/Components/BrandLogo';
import CookieConsentBanner from '@/Components/CookieConsentBanner';
import Dropdown from '@/Components/Dropdown';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import ModerationTodoBell from '@/Components/ModerationTodoBell';
import NavLink from '@/Components/NavLink';
import NavDropdown, { NavDropdownLink } from '@/Components/NavDropdown';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';
import { useTranslations } from '@/lib/translations';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const page = usePage<PageProps>();
    const user = page.props.auth.user;
    const { translations } = page.props;
    const t = useTranslations(translations);
    const canReview = user?.permissions?.includes('review_mods') || user?.permissions?.includes('moderate_comments') || user?.permissions?.includes('handle_reports');
    const canManageAdminData = user?.permissions?.includes('manage_users') || user?.permissions?.includes('manage_categories') || user?.permissions?.includes('manage_faqs') || user?.permissions?.includes('manage_ranks') || user?.permissions?.includes('manage_settings');
    const canManageMods = user?.permissions?.includes('review_mods') || user?.permissions?.includes('edit_any_mod') || user?.permissions?.includes('delete_any_mod') || user?.roles?.includes('admin') || false;
    const moderationTodos = page.props.moderationTodos;

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    return (
        <div className="flex min-h-screen flex-col bg-gray-100 dark:bg-gray-900">
            <nav className="border-b border-gray-100 bg-white dark:border-gray-700 dark:bg-gray-800">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <BrandLogo imageClassName="block h-9 max-w-[160px] object-contain" fallbackIconClassName="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" textClassName="hidden text-lg font-black tracking-tight text-gray-900 dark:text-white lg:inline" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 lg:-my-px lg:ms-10 lg:flex">
                                <NavLink
                                    href={route('mods.index')}
                                    active={route().current('mods.index') || route().current('home')}
                                >
                                    {t('navigation.mods', 'Mods')}
                                </NavLink>
                                <NavLink
                                    href={route('dashboard')}
                                    active={route().current('dashboard')}
                                >
                                    {t('navigation.dashboard', 'Dashboard')}
                                </NavLink>
                                <NavLink
                                    href={route('mods.mine')}
                                    active={route().current('mods.mine')}
                                >
                                    {t('navigation.my_mods', 'My Mods')}
                                </NavLink>
                                {canReview && (
                                    <NavDropdown
                                        label={t('navigation.moderation_group', 'Moderation')}
                                        active={route().current('admin.moderation.*') || route().current('admin.reports.*')}
                                    >
                                        <NavDropdownLink
                                            href={route('admin.moderation.index')}
                                            active={route().current('admin.moderation.*')}
                                        >
                                            {t('navigation.moderation_queue', 'Moderation Queue')}
                                        </NavDropdownLink>
                                        <NavDropdownLink
                                            href={route('admin.reports.index')}
                                            active={route().current('admin.reports.*')}
                                        >
                                            {t('navigation.reports', 'Reports')}
                                        </NavDropdownLink>
                                    </NavDropdown>
                                )}
                                {canManageAdminData && (
                                    <NavDropdown
                                        label={t('navigation.admin', 'Admin')}
                                        active={route().current('admin.users.*') || route().current('admin.categories.*') || route().current('admin.faqs.*') || route().current('admin.ranks.*') || route().current('admin.rank-point-rules.*') || route().current('admin.settings.*') || route().current('admin.email-templates.*') || route().current('admin.mods.*')}
                                    >
                                        {canManageMods && (
                                            <NavDropdownLink
                                                href={route('admin.mods.index')}
                                                active={route().current('admin.mods.*')}
                                            >
                                                {t('navigation.admin_mods', 'All Mods')}
                                            </NavDropdownLink>
                                        )}
                                        <NavDropdownLink
                                            href={route('admin.users.index')}
                                            active={route().current('admin.users.*')}
                                        >
                                            {t('navigation.users', 'Users')}
                                        </NavDropdownLink>
                                    <NavDropdownLink
                                        href={route('admin.categories.index')}
                                        active={route().current('admin.categories.*')}
                                    >
                                        {t('navigation.categories', 'Categories')}
                                    </NavDropdownLink>
                                    <NavDropdownLink
                                        href={route('admin.faqs.index')}
                                        active={route().current('admin.faqs.*')}
                                    >
                                        {t('navigation.faqs', 'FAQs')}
                                    </NavDropdownLink>
                                        <NavDropdownLink
                                            href={route('admin.ranks.index')}
                                            active={route().current('admin.ranks.*')}
                                        >
                                            {t('navigation.ranks', 'Ranks')}
                                        </NavDropdownLink>
                                        <NavDropdownLink
                                            href={route('admin.rank-point-rules.index')}
                                            active={route().current('admin.rank-point-rules.*')}
                                        >
                                            {t('navigation.rank_point_rules', 'Point Rules')}
                                        </NavDropdownLink>
                                        <NavDropdownLink
                                            href={route('admin.settings.index')}
                                            active={route().current('admin.settings.*')}
                                        >
                                            {t('navigation.settings', 'Settings')}
                                        </NavDropdownLink>
                                        <NavDropdownLink
                                            href={route('admin.email-templates.index')}
                                            active={route().current('admin.email-templates.*')}
                                        >
                                            {t('navigation.email_templates', 'Email Templates')}
                                        </NavDropdownLink>
                                    </NavDropdown>
                                )}
                            </div>
                        </div>

                        <div className="hidden lg:ms-6 lg:flex lg:items-center">
                            <LanguageSwitcher />
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-300"
                                            >
                                                <svg className="-ms-1 me-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0v.75H4.5v-.75z" />
                                                </svg>
                                                {user?.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            {t('navigation.profile', 'Profile')}
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            {t('navigation.logout', 'Log Out')}
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                            {canReview && moderationTodos && (
                                <ModerationTodoBell todos={moderationTodos} t={t} />
                            )}
                        </div>

                        <div className="-me-2 flex items-center lg:hidden">
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none dark:text-gray-500 dark:hover:bg-gray-900 dark:hover:text-gray-400 dark:focus:bg-gray-900 dark:focus:text-gray-400"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' lg:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href={route('mods.index')}
                            active={route().current('mods.index') || route().current('home')}
                        >
                            {t('navigation.mods', 'Mods')}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            {t('navigation.dashboard', 'Dashboard')}
                        </ResponsiveNavLink>
                        <ResponsiveNavLink
                            href={route('mods.mine')}
                            active={route().current('mods.mine')}
                        >
                            {t('navigation.my_mods', 'My Mods')}
                        </ResponsiveNavLink>
                        {canReview && (
                            <>
                                <div className="mt-4 flex items-center justify-between px-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <span>{t('navigation.moderation_group', 'Moderation')}</span>
                                    {moderationTodos && moderationTodos.total > 0 && (
                                        <span className="rounded-full bg-red-600 px-2 py-0.5 text-xs text-white">
                                            {moderationTodos.total}
                                        </span>
                                    )}
                                </div>
                                <ResponsiveNavLink
                                    href={route('admin.moderation.index')}
                                    active={route().current('admin.moderation.*')}
                                >
                                    <span className="inline-flex w-full items-center justify-between">
                                        <span>{t('navigation.moderation_queue', 'Moderation Queue')}</span>
                                        {moderationTodos && moderationTodos.pending_mods + moderationTodos.pending_versions > 0 && (
                                            <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900 dark:text-amber-100">
                                                {moderationTodos.pending_mods + moderationTodos.pending_versions}
                                            </span>
                                        )}
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.reports.index')}
                                    active={route().current('admin.reports.*')}
                                >
                                    <span className="inline-flex w-full items-center justify-between">
                                        <span>{t('navigation.reports', 'Reports')}</span>
                                        {moderationTodos && moderationTodos.pending_reports > 0 && (
                                            <span className="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-bold text-rose-800 dark:bg-rose-900 dark:text-rose-100">
                                                {moderationTodos.pending_reports}
                                            </span>
                                        )}
                                    </span>
                                </ResponsiveNavLink>
                            </>
                        )}
                        {canManageAdminData && (
                            <>
                                <div className="mt-4 px-4 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    {t('navigation.admin', 'Admin')}
                                </div>
                                {canManageMods && (
                                    <ResponsiveNavLink
                                        href={route('admin.mods.index')}
                                        active={route().current('admin.mods.*')}
                                    >
                                        {t('navigation.admin_mods', 'All Mods')}
                                    </ResponsiveNavLink>
                                )}
                                <ResponsiveNavLink
                                    href={route('admin.users.index')}
                                    active={route().current('admin.users.*')}
                                >
                                    {t('navigation.users', 'Users')}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.categories.index')}
                                    active={route().current('admin.categories.*')}
                                >
                                    {t('navigation.categories', 'Categories')}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.faqs.index')}
                                    active={route().current('admin.faqs.*')}
                                >
                                    {t('navigation.faqs', 'FAQs')}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.ranks.index')}
                                    active={route().current('admin.ranks.*')}
                                >
                                    {t('navigation.ranks', 'Ranks')}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.rank-point-rules.index')}
                                    active={route().current('admin.rank-point-rules.*')}
                                >
                                    {t('navigation.rank_point_rules', 'Point Rules')}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.settings.index')}
                                    active={route().current('admin.settings.*')}
                                >
                                    {t('navigation.settings', 'Settings')}
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    href={route('admin.email-templates.index')}
                                    active={route().current('admin.email-templates.*')}
                                >
                                    {t('navigation.email_templates', 'Email Templates')}
                                </ResponsiveNavLink>
                            </>
                        )}
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4 dark:border-gray-600">
                        <div className="px-4">
                            <div className="mb-2">
                                <LanguageSwitcher />
                            </div>
                            <div className="text-base font-medium text-gray-800 dark:text-gray-200">
                                <svg className="-ms-1 me-2 inline-block h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0v.75H4.5v-.75z" />
                                </svg>
                                {user?.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500">
                                {user?.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                {t('navigation.profile', 'Profile')}
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                {t('navigation.logout', 'Log Out')}
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="bg-white shadow dark:bg-gray-800">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main className="flex-1">{children}</main>
            <AppFooter />
            <CookieConsentBanner />
        </div>
    );
}
