import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useEffect, useRef } from 'react';
import { useTranslations } from '@/lib/translations';

declare global {
    interface Window {
        turnstile?: {
            render: (container: HTMLElement, options: { sitekey: string; callback: (token: string) => void }) => string;
            reset: (widgetId?: string) => void;
        };
    }
}

type RegisterProps = {
    requiresCaptcha: boolean;
    turnstileSiteKey: string | null;
};

export default function Register({ requiresCaptcha, turnstileSiteKey }: RegisterProps) {
    const { translations } = usePage().props;
    const t = useTranslations(translations);
    const turnstileRef = useRef<HTMLDivElement>(null);
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        website: '',
        registration_started_at: Date.now(),
        turnstile_token: '',
    });

    useEffect(() => {
        if (!requiresCaptcha || !turnstileSiteKey || !turnstileRef.current) {
            return;
        }

        const renderTurnstile = () => {
            if (!window.turnstile || !turnstileRef.current || turnstileRef.current.childElementCount > 0) {
                return;
            }

            window.turnstile.render(turnstileRef.current, {
                sitekey: turnstileSiteKey,
                callback: (token: string) => setData('turnstile_token', token),
            });
        };

        if (window.turnstile) {
            renderTurnstile();

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.defer = true;
        script.onload = renderTurnstile;
        document.head.appendChild(script);
    }, [requiresCaptcha, setData, turnstileSiteKey]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title={t('auth.register', 'Register')} />

            <form onSubmit={submit}>
                <div className="absolute left-[-10000px] top-auto h-px w-px overflow-hidden" aria-hidden="true">
                    <InputLabel htmlFor="website" value={t('auth.website', 'Website')} />
                    <TextInput
                        id="website"
                        name="website"
                        value={data.website}
                        tabIndex={-1}
                        autoComplete="off"
                        onChange={(e) => setData('website', e.target.value)}
                    />
                </div>

                <input type="hidden" name="registration_started_at" value={data.registration_started_at} />

                <div>
                    <InputLabel htmlFor="name" value={t('auth.name', 'Name')} />

                    <TextInput
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1 block w-full"
                        autoComplete="name"
                        isFocused={true}
                        onChange={(e) => setData('name', e.target.value)}
                        required
                    />

                    <InputError message={errors.name} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="email" value={t('auth.email', 'Email')} />

                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1 block w-full"
                        autoComplete="username"
                        onChange={(e) => setData('email', e.target.value)}
                        required
                    />

                    <InputError message={errors.email} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel htmlFor="password" value={t('auth.password', 'Password')} />

                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) => setData('password', e.target.value)}
                        required
                    />

                    <InputError message={errors.password} className="mt-2" />
                </div>

                <div className="mt-4">
                    <InputLabel
                        htmlFor="password_confirmation"
                        value={t('auth.confirm_password_label', 'Confirm Password')}
                    />

                    <TextInput
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1 block w-full"
                        autoComplete="new-password"
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                        required
                    />

                    <InputError
                        message={errors.password_confirmation}
                        className="mt-2"
                    />
                </div>

                {requiresCaptcha && turnstileSiteKey && (
                    <div className="mt-4">
                        <p className="mb-2 text-sm text-gray-600 dark:text-gray-400">
                            {t('auth.captcha_required', 'Please complete the security check to continue.')}
                        </p>
                        <div ref={turnstileRef} />
                        <InputError message={errors.turnstile_token} className="mt-2" />
                    </div>
                )}

                <div className="mt-4 flex items-center justify-end">
                    <Link
                        href={route('login')}
                        className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:text-gray-400 dark:hover:text-gray-100 dark:focus:ring-offset-gray-800"
                    >
                        {t('auth.already_registered', 'Already registered?')}
                    </Link>

                    <PrimaryButton className="ms-4" disabled={processing}>
                        {t('auth.register', 'Register')}
                    </PrimaryButton>
                </div>
            </form>
        </GuestLayout>
    );
}
