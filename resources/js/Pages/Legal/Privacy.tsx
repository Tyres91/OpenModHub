import PublicLayout from '@/Layouts/PublicLayout';
import { PageProps } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';
import { useTranslations } from '@/lib/translations';

type LegalSettings = {
    operator_name: string;
    privacy_contact: string;
    email: string;
};

export default function Privacy({ googleTagManagerId, legalSettings }: PageProps<{ googleTagManagerId: string; legalSettings: LegalSettings }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const contact = legalSettings.privacy_contact || legalSettings.email || t('legal.not_configured', 'Not configured yet.');

    return (
        <PublicLayout>
            <Head title={t('legal.privacy', 'Privacy Policy')} />
            <div className="mx-auto max-w-3xl px-6 py-14">
                <h1 className="text-4xl font-black tracking-tight">{t('legal.privacy', 'Privacy Policy')}</h1>
                <div className="mt-8 space-y-8 rounded-3xl bg-white/5 p-8 text-slate-200 ring-1 ring-white/10">
                    <Section title={t('legal.controller', 'Controller')}>
                        <p>{legalSettings.operator_name || t('legal.not_configured', 'Not configured yet.')}</p>
                        <p>{contact}</p>
                    </Section>

                    <Section title={t('legal.privacy_general_title', 'General processing')}>
                        <p>{t('legal.privacy_general_text', 'We process personal data only where necessary to operate this website, provide user accounts, handle mod submissions, moderation, comments, reports, security, and language preferences.')}</p>
                    </Section>

                    <Section title={t('legal.cookies_title', 'Cookies and local storage')}>
                        <p>{t('legal.cookies_text', 'Necessary cookies and local storage are used for sessions, CSRF protection, language selection, and saving your cookie preferences. Optional analytics storage is used only after your consent.')}</p>
                    </Section>

                    <Section title={t('legal.analytics_title', 'Analytics and Google Tag Manager')}>
                        {googleTagManagerId ? (
                            <p>{t('legal.analytics_enabled_text', 'Google Tag Manager can be loaded after your explicit analytics consent. You can withdraw or change this consent at any time via the cookie settings link in the footer.')}</p>
                        ) : (
                            <p>{t('legal.analytics_disabled_text', 'Analytics tracking is currently not configured.')}</p>
                        )}
                    </Section>

                    <Section title={t('legal.rights_title', 'Your rights')}>
                        <p>{t('legal.rights_text', 'Depending on applicable law, you may have rights to access, rectification, erasure, restriction, objection, data portability, and complaint to a supervisory authority.')}</p>
                    </Section>
                </div>
            </div>
        </PublicLayout>
    );
}

function Section({ title, children }: { title: string; children: ReactNode }) {
    return (
        <section>
            <h2 className="text-xl font-bold text-white">{title}</h2>
            <div className="mt-3 space-y-3 text-sm leading-6">{children}</div>
        </section>
    );
}
