import PublicLayout from '@/Layouts/PublicLayout';
import { PageProps, PublicFaq } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { useTranslations } from '@/lib/translations';

function FaqItem({ faq }: { faq: PublicFaq }) {
    const [isOpen, setIsOpen] = useState(false);

    return (
        <div className={`rounded-xl border transition-all duration-200 ${
            isOpen
                ? 'bg-white/[0.07] border-cyan-400/30'
                : 'bg-white/5 border-white/10 hover:bg-white/10'
        }`}>
            <button
                onClick={() => setIsOpen(!isOpen)}
                className="flex w-full items-center justify-between px-4 py-3 text-left"
            >
                <span className="pr-4 text-lg font-semibold text-white">{faq.question}</span>
                <svg
                    className={`h-5 w-5 shrink-0 transition-all duration-200 ${isOpen ? 'rotate-180 text-cyan-400' : 'text-slate-400'}`}
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth="2"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div
                className={`overflow-hidden transition-all duration-300 ${
                    isOpen ? 'max-h-[2000px] opacity-100' : 'max-h-0 opacity-0'
                }`}
            >
                <div className="border-t border-white/5 px-4 pb-4 pt-2">
                    <div
                        className="prose prose-invert prose-sm max-w-none overflow-y-auto text-slate-300"
                        dangerouslySetInnerHTML={{ __html: faq.answer }}
                    />
                </div>
            </div>
        </div>
    );
}

export default function Index({ faqs }: PageProps<{ faqs: PublicFaq[] }>) {
    const { translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);

    return (
        <PublicLayout>
            <Head title={t('faqs.title', 'FAQs')} />

            <div className="mx-auto max-w-7xl px-6 py-12">
                <div className="mb-8">
                    <h1 className="text-3xl font-black text-white">{t('faqs.heading', 'Frequently Asked Questions')}</h1>
                    <p className="mt-2 text-slate-400">{t('faqs.subtitle', 'Find answers to common questions about OpenModHub.')}</p>
                </div>

                {faqs.length === 0 ? (
                    <div className="py-12 text-center text-slate-400">
                        {t('faqs.no_faqs', 'No FAQs available yet.')}
                    </div>
                ) : (
                    <div className="space-y-2">
                        {faqs.map((faq) => <FaqItem key={faq.id} faq={faq} />)}
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
