import { InertiaLinkProps, Link } from '@inertiajs/react';
import { PropsWithChildren, useState } from 'react';

interface NavDropdownLinkProps extends InertiaLinkProps {
    active?: boolean;
}

export default function NavDropdown({
    label,
    active = false,
    children,
}: PropsWithChildren<{ label: string; active?: boolean }>) {
    const [isOpen, setIsOpen] = useState(false);

    const baseClasses =
        'inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none';

    const activeClasses =
        'border-indigo-400 text-gray-900 focus:border-indigo-700 dark:border-indigo-600 dark:text-gray-100';

    const inactiveClasses =
        'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 focus:border-gray-300 focus:text-gray-700 dark:text-gray-400 dark:hover:border-gray-700 dark:hover:text-gray-300 dark:focus:border-gray-700 dark:focus:text-gray-300';

    return (
        <div className="relative inline-flex items-center">
            <button
                onClick={() => setIsOpen(!isOpen)}
                className={`${baseClasses} ${active ? activeClasses : inactiveClasses}`}
            >
                {label}
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

            {isOpen && (
                <div
                    className="absolute left-0 top-full z-50 mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700"
                    onMouseLeave={() => setIsOpen(false)}
                >
                    {children}
                </div>
            )}
        </div>
    );
}

export function NavDropdownLink({
    active = false,
    className = '',
    children,
    ...props
}: NavDropdownLinkProps) {
    return (
        <Link
            {...props}
            className={`block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out focus:outline-none ${
                active
                    ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300'
                    : 'text-gray-700 hover:bg-gray-100 focus:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 dark:focus:bg-gray-800'
            } ${className}`}
        >
            {children}
        </Link>
    );
}
