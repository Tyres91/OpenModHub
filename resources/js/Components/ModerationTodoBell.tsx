import Dropdown from '@/Components/Dropdown';
import { PageProps } from '@/types';
import { Link } from '@inertiajs/react';

type ModerationTodos = NonNullable<PageProps['moderationTodos']>;

type ModerationTodoBellProps = {
    todos: ModerationTodos;
    t: (key: string, fallback: string) => string;
    variant?: 'light' | 'dark';
};

export default function ModerationTodoBell({ todos, t, variant = 'light' }: ModerationTodoBellProps) {
    const moderationCount = todos.pending_mods + todos.pending_versions;
    const primaryHref = moderationCount > 0 ? route('admin.moderation.index') : route('admin.reports.index');
    const hasOpenTasks = todos.total > 0;

    const activeClasses = variant === 'dark'
        ? 'border-amber-300/50 bg-amber-300/15 text-amber-100 hover:bg-amber-300/25 focus:ring-amber-300'
        : 'border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100 focus:ring-amber-500 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200 dark:hover:bg-amber-900';

    const idleClasses = variant === 'dark'
        ? 'border-white/15 bg-white/5 text-slate-200 hover:bg-white/10 focus:ring-slate-300'
        : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50 focus:ring-gray-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700';

    return (
        <div className="relative ms-4 shrink-0">
            <Dropdown>
                <Dropdown.Trigger>
                    <span className="inline-flex rounded-md">
                        <button
                            type="button"
                            className={
                                'relative inline-flex h-10 w-10 items-center justify-center rounded-full border transition focus:outline-none focus:ring-2 focus:ring-offset-2 ' +
                                (variant === 'dark' ? 'focus:ring-offset-slate-950 ' : 'dark:focus:ring-offset-gray-800 ') +
                                (hasOpenTasks ? activeClasses : idleClasses)
                            }
                            aria-label={t('navigation.open_tasks', 'Open tasks')}
                        >
                            <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.5">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            {hasOpenTasks && (
                                <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-xs font-bold leading-none text-white ring-2 ring-white dark:ring-gray-800">
                                    {todos.total}
                                </span>
                            )}
                        </button>
                    </span>
                </Dropdown.Trigger>
                <Dropdown.Content contentClasses="bg-white py-2 dark:bg-gray-700">
                    <div className="px-4 pb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-300">
                        {t('navigation.open_tasks', 'Open tasks')}
                    </div>
                    <Link href={primaryHref} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                        <span className="font-semibold">{todos.total}</span> {t('navigation.open_tasks_total', 'open tasks')}
                    </Link>
                    {!hasOpenTasks && (
                        <div className="px-4 pb-2 text-sm text-gray-500 dark:text-gray-300">
                            {t('navigation.no_open_tasks', 'No open tasks.')}
                        </div>
                    )}
                    <Link href={route('admin.moderation.index')} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                        {t('navigation.pending_reviews', 'Mod reviews')}: <span className="font-semibold">{moderationCount}</span>
                    </Link>
                    <Link href={route('admin.reports.index')} className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                        {t('navigation.pending_reports', 'Reports')}: <span className="font-semibold">{todos.pending_reports}</span>
                    </Link>
                </Dropdown.Content>
            </Dropdown>
        </div>
    );
}
