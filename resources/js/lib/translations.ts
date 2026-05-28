function resolveKey(translations: Record<string, unknown>, key: string): string | null {
    const parts = key.split('.');
    let current: unknown = translations;

    for (const part of parts) {
        if (typeof current !== 'object' || current === null) {
            return null;
        }
        current = (current as Record<string, unknown>)[part];
    }

    return typeof current === 'string' ? current : null;
}

export function t(translations: Record<string, unknown>, key: string, fallback?: string): string {
    return resolveKey(translations, key) ?? fallback ?? key;
}

export function useTranslations(translations: Record<string, unknown>) {
    return (key: string, fallback?: string) => t(translations, key, fallback);
}
