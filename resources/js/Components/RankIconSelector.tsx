import { Icon } from '@mdi/react';
import { useState, useMemo, useRef, useEffect } from 'react';
import { rankIconOptions } from '@/Components/RankIcon';
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';
import { useTranslations } from '@/lib/translations';

interface RankIconSelectorProps {
    value: string;
    onChange: (value: string) => void;
    className?: string;
}

export default function RankIconSelector({ value, onChange, className = '' }: RankIconSelectorProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const wrapperRef = useRef<HTMLDivElement>(null);
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    const selected = rankIconOptions.find((o) => o.value === value) ?? rankIconOptions[0];

    const filteredOptions = useMemo(() => {
        if (!search) return rankIconOptions;
        const lower = search.toLowerCase();
        return rankIconOptions.filter((o) => o.label.toLowerCase().includes(lower));
    }, [search]);

    useEffect(() => {
        if (!isOpen) return;

        const handleClickOutside = (e: MouseEvent) => {
            if (wrapperRef.current && !wrapperRef.current.contains(e.target as Node)) {
                setIsOpen(false);
                setSearch('');
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [isOpen]);

    return (
        <div ref={wrapperRef} className={`relative ${className}`}>
            <button
                type="button"
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-left text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
            >
                {selected.path && (
                    <span className="flex size-5 shrink-0 items-center justify-center text-gray-600 dark:text-gray-300">
                        <Icon path={selected.path} size={0.8} />
                    </span>
                )}
                <span className="flex-1 truncate text-gray-700 dark:text-gray-200">{selected.label}</span>
                <svg className="h-4 w-4 shrink-0 text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fillRule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clipRule="evenodd" />
                </svg>
            </button>

            {isOpen && (
                <div className="absolute left-0 top-full z-50 mt-1 w-full rounded-md border border-gray-200 bg-white py-1 shadow-lg dark:border-gray-700 dark:bg-gray-800">
                    <div className="px-2 py-1">
                        <input
                            type="text"
                            autoFocus
                            placeholder={t('common.search_icons', 'Search icons...')}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="w-full rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                            onClick={(e) => e.stopPropagation()}
                        />
                    </div>
                    <div className="max-h-72 overflow-y-auto">
                        {filteredOptions.length === 0 ? (
                            <div className="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">{t('common.no_icons_found', 'No icons found')}</div>
                        ) : (
                            filteredOptions.map((option) => (
                                <button
                                    key={option.value}
                                    type="button"
                                    onClick={() => {
                                        onChange(option.value);
                                        setIsOpen(false);
                                        setSearch('');
                                    }}
                                    className={`flex w-full items-center gap-2 px-3 py-2 text-sm ${
                                        option.value === value
                                            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300'
                                            : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700'
                                    }`}
                                >
                                    {option.path && (
                                        <span className="flex size-5 shrink-0 items-center justify-center text-gray-600 dark:text-gray-300">
                                            <Icon path={option.path} size={0.8} />
                                        </span>
                                    )}
                                    <span className="truncate">{option.label}</span>
                                </button>
                            ))
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
