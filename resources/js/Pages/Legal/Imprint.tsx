import PublicLayout from '@/Layouts/PublicLayout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslations } from '@/lib/translations';

type LegalSettings = {
    operator_name: string;
    represented_by: string;
    street: string;
    postal_code: string;
    city: string;
    country: string;
    email: string;
    phone: string;
    vat_id: string;
    additional_info: string;
};

export default function Imprint({ legalSettings }: PageProps<{ legalSettings: LegalSettings }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    return (
        <PublicLayout>
            <Head title={t('legal.imprint', 'Imprint')} />
            <div className="mx-auto max-w-3xl px-6 py-14">
                <h1 className="text-4xl font-black tracking-tight">{t('legal.imprint', 'Imprint')}</h1>
                <div className="mt-8 space-y-8 rounded-3xl bg-white/5 p-8 text-slate-200 ring-1 ring-white/10">
                    <Section title={t('legal.operator', 'Operator')}>
                        <AddressLine value={legalSettings.operator_name} fallback={t('legal.not_configured', 'Not configured yet.')} />
                        <AddressLine value={legalSettings.street} />
                        <AddressLine value={`${legalSettings.postal_code} ${legalSettings.city}`.trim()} />
                        <AddressLine value={legalSettings.country} />
                    </Section>

                    {legalSettings.represented_by && (
                        <Section title={t('legal.represented_by', 'Represented by')}>
                            <p>{legalSettings.represented_by}</p>
                        </Section>
                    )}

                    <Section title={t('legal.contact', 'Contact')}>
                        <AddressLine value={legalSettings.email ? `${t('auth.email', 'Email')}: ${legalSettings.email}` : ''} />
                        <AddressLine value={legalSettings.phone ? `${t('admin.settings.legal_phone', 'Phone')}: ${legalSettings.phone}` : ''} />
                    </Section>

                    {legalSettings.vat_id && (
                        <Section title={t('legal.vat_id', 'VAT ID')}>
                            <p>{legalSettings.vat_id}</p>
                        </Section>
                    )}

                    {legalSettings.additional_info && (
                        <Section title={t('legal.additional_info', 'Additional information')}>
                            <p className="whitespace-pre-line">{legalSettings.additional_info}</p>
                        </Section>
                    )}
                </div>
            </div>
        </PublicLayout>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section>
            <h2 className="text-xl font-bold text-white">{title}</h2>
            <div className="mt-3 space-y-1 text-sm leading-6">{children}</div>
        </section>
    );
}

function AddressLine({ value, fallback }: { value: string; fallback?: string }) {
    const normalized = value.trim();

    if (!normalized && !fallback) {
        return null;
    }

    return <p>{normalized || fallback}</p>;
}
