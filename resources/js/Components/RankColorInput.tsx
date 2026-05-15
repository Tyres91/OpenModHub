import { useState, useEffect } from 'react';

interface RankColorInputProps {
    value: string;
    onChange: (value: string) => void;
    className?: string;
}

const isValidHex = (val: string) => /^#[0-9A-Fa-f]{6}$/.test(val);

export default function RankColorInput({ value, onChange, className = '' }: RankColorInputProps) {
    const [textValue, setTextValue] = useState(value);

    useEffect(() => {
        setTextValue(value);
    }, [value]);

    const safeColorValue = isValidHex(value) ? value : '#4f46e5';

    const handleTextChange = (raw: string) => {
        setTextValue(raw);
        const cleaned = raw.startsWith('#') ? raw : '#' + raw.replace(/[^0-9A-Fa-f]/g, '');
        if (isValidHex(cleaned)) {
            onChange(cleaned);
        }
    };

    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <input
                type="color"
                value={safeColorValue}
                onChange={(e) => {
                    onChange(e.target.value);
                    setTextValue(e.target.value);
                }}
                className="h-9 w-9 shrink-0 cursor-pointer rounded-md border border-gray-300 p-0.5 dark:border-gray-700"
            />
            <input
                type="text"
                value={textValue}
                onChange={(e) => handleTextChange(e.target.value)}
                maxLength={7}
                placeholder="#4f46e5"
                className="flex-1 rounded-md border border-gray-300 px-3 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 dark:focus:border-indigo-600 dark:focus:ring-indigo-600"
            />
        </div>
    );
}
