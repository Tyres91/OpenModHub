import { SecurityCheck, PageProps } from '@/types';
import { usePage } from '@inertiajs/react';
import { useTranslations } from '@/lib/translations';

const statusStyles: Record<SecurityCheck['status'] | 'missing', string> = {
    missing: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    not_submitted: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    pending: 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-200',
    clean: 'bg-green-100 text-green-800 dark:bg-green-500/10 dark:text-green-200',
    suspicious: 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
    failed: 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-200',
};

export default function SecurityCheckBadge({
    securityCheck,
    withLabel = false,
}: {
    securityCheck?: SecurityCheck | null;
    withLabel?: boolean;
}) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const status = securityCheck?.status ?? 'missing';
    const label = t(`security.status_${status}`, status.replace('_', ' '));

    return (
        <span className={`inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide ${statusStyles[status]}`}>
            {withLabel ? `${t('security.heading', 'Security check')}: ${label}` : label}
        </span>
    );
}
