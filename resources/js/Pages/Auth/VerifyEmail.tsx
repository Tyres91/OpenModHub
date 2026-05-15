import PrimaryButton from '@/Components/PrimaryButton';
import GuestLayout from '@/Layouts/GuestLayout';
import { PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { useTranslations } from '@/lib/translations';

export default function VerifyEmail({ status }: { status?: string }) {
    const { flash, translations } = usePage<PageProps>().props;
    const t = useTranslations(translations);
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title={t('auth.verify_email', 'Email Verification')} />

            <div className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {t('auth.verify_email_description', "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.")}
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-sm font-medium text-green-600 dark:text-green-400">
                    {t('auth.verification_link_sent', 'A new verification link has been sent to the email address you provided during registration.')}
                </div>
            )}

            {flash.debugVerificationUrl && (
                <div className="mb-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                    <p className="font-semibold">{t('auth.debug_verification_url_heading', 'Debug verification URL')}</p>
                    <p className="mt-1">{t('auth.debug_verification_url_hint', 'Debug mode is enabled. Use this local development link to verify the newly registered account.')}</p>
                    <a className="mt-2 block break-all font-mono text-xs underline" href={flash.debugVerificationUrl}>
                        {flash.debugVerificationUrl}
                    </a>
                </div>
            )}

            <form onSubmit={submit}>
                <div className="mt-4 flex items-center justify-between">
                    <PrimaryButton disabled={processing}>
                        {t('auth.resend_verification_email', 'Resend Verification Email')}
                    </PrimaryButton>

                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        {t('auth.log_out', 'Log Out')}
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
