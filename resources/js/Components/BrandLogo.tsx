import ApplicationLogo from '@/Components/ApplicationLogo';
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/react';

type BrandLogoProps = {
    imageClassName?: string;
    textClassName?: string;
    fallbackIconClassName?: string;
    wrapperClassName?: string;
};

export default function BrandLogo({
    imageClassName = 'h-9 w-auto',
    textClassName = 'text-xl font-black tracking-tight',
    fallbackIconClassName = 'h-9 w-auto fill-current',
    wrapperClassName = 'flex items-center gap-3',
}: BrandLogoProps) {
    const { branding } = usePage<PageProps>().props;
    const logoText = branding.logoText.trim();
    const imageAltText = logoText || 'Site logo';

    return (
        <span className={wrapperClassName}>
            {branding.logoUrl ? (
                <img src={branding.logoUrl} alt={imageAltText} className={imageClassName} />
            ) : (
                <ApplicationLogo className={fallbackIconClassName} />
            )}
            {branding.showLogoText && logoText.length > 0 && <span className={textClassName}>{logoText}</span>}
        </span>
    );
}
