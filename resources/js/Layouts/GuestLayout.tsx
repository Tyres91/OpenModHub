import BrandLogo from '@/Components/BrandLogo';
import LanguageSwitcher from '@/Components/LanguageSwitcher';
import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="relative flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0 dark:bg-gray-900">
            <div className="absolute right-4 top-4">
                <LanguageSwitcher />
            </div>

            <div>
                <Link href="/">
                    <BrandLogo imageClassName="h-20 max-w-[220px] object-contain" fallbackIconClassName="h-20 w-20 fill-current text-gray-500" textClassName="text-2xl font-black tracking-tight text-gray-900 dark:text-white" wrapperClassName="flex flex-col items-center gap-3" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md sm:max-w-md sm:rounded-lg dark:bg-gray-800">
                {children}
            </div>
        </div>
    );
}
